<?php

namespace Doofinder\WP\Api;

use Doofinder\WP\Api\Management\Errors\BadRequest;
use Doofinder\WP\Api\Management\Errors\IndexingInProgress;
use Doofinder\WP\Api\Management\Client;
use Doofinder\WP\Api\Management\Errors\NotFound;
use Doofinder\WP\Api\Management\Errors\TypeAlreadyExists;
use Doofinder\WP\Api\Management\SearchEngine;
use Doofinder\WP\Indexing_Data;
use Doofinder\WP\Log;
use Doofinder\WP\Settings;

defined( 'ABSPATH' ) or die();

class Doofinder_Api implements Api_Wrapper {

	/**
	 * Instance of a class used to log to a file.
	 *
	 * @var Log
	 */
	private $log;

	/**
	 * Search engine we'll index the items with.
	 *
	 * @var SearchEngine
	 */
	private $search_engine;

	public function __construct() {
		$this->log = new Log();

		try {
			$this->search_engine = $this->get_search_engine();
		} catch ( \Exception $exception ) {
			$this->log->log( $exception );
		}

		if ( $this->search_engine ) {
			$this->search_engine = new Throttle( $this->search_engine );
		}
	}

	/**
	 * Update the data of a single item in the API.
	 *
	 * @param string $item_type
	 * @param int    $id
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function update_item( $item_type, $id, $data ) {
		// Doofinder API throws exceptions if something goes wrong.
		try {
			if ( ! $this->search_engine ) {
				$this->log->log( 'Invalid search engine.' );

				return Api_Status::$invalid_search_engine;
			}

			// Item type may not exist, but is required.
			// Let's create it.
			$this->maybe_create_type( $item_type );

			// Send item to Doofinder.
			$this->search_engine->updateItem( $item_type, $id, $data );
		} catch ( \Exception $exception ) {
			$this->log->log( $exception );

			return Api_Status::$unknown_error;
		}
	}

	/**
	 * Remove given item from indexing.
	 *
	 * @param string $item_type
	 * @param int    $id
	 *
	 * @return mixed
	 */
	public function remove_item( $item_type, $id ) {
		// Doofinder API throws exceptions if something goes wrong.
		try {
			if ( ! $this->search_engine ) {
				$this->log->log( 'Invalid search engine.' );

				return Api_Status::$invalid_search_engine;
			}

			// Item type may not exist, but is required.
			// Let's create it.
			$this->maybe_create_type( $item_type );

			// Send item to Doofinder.
			$this->search_engine->deleteItem( $item_type, $id );
		} catch ( \Exception $exception ) {
			$this->log->log( $exception );

			return Api_Status::$unknown_error;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function send_batch( $items_type, array $items ) {
		// Doofinder API will throw an exception in case of invalid token
		// or something like that.
		try {
			if ( ! $this->search_engine ) {
				$this->log->log( 'Invalid search engine.' );

				return Api_Status::$invalid_search_engine;
			}

			// Check if we need to add the type in our own status.
			// This should reduce the number of requests made to the
			// Doofinder API.
			$indexing_data = Indexing_Data::instance();
			if ( ! $indexing_data->has( 'post_types_readded', $items_type ) ) {
				// Create the type.
				$this->search_engine->addType( $items_type );

				// Mark it in our status.
				$indexing_data->set( 'post_types_readded', $items_type );
			}

			// Send the items to Doofinder.
			$this->search_engine->addItems(
				$items_type,
				$items
			);

			return Api_Status::$success;
		} catch ( IndexingInProgress $exception ) {
			// This exception is thrown after we've sent request to delete a type
			// and the server is not finished processing it yet.
			// We'll have to retry the request in a moment.

			$this->log->log( $exception );

			return Api_Status::$indexing_in_progress;
		} catch ( TypeAlreadyExists $exception ) {
			// This exception is thrown if we try to create a type that already exists.
			// We do check our status before trying to create the type, but in case
			// something goes wrong with our status let's catch it.

			$this->log->log( $exception );

			if ( ! isset( $indexing_data ) ) {
				$indexing_data = Indexing_Data::instance();
			}

			$indexing_data->set( 'post_types_readded', $items_type );

			// Try adding the item.
			try {
				$this->search_engine->addItems(
					$items_type,
					$items
				);

				return Api_Status::$success;
			} catch ( \Exception $exception ) {
				$this->log->log( $exception );

				return Api_Status::$unknown_error;
			}
		} catch ( BadRequest $exception ) {
			// This exception is thrown when we try to add items to a type that does
			// not exist. We need to add a type and then try adding item again.

			$this->log->log( $exception );

			if ( preg_match( '/undefined.+type/i', $exception->getMessage() ) === 1 ) {
				try {
					// Create the type.
					$this->search_engine->addType( $items_type );

					// Send the items to Doofinder.
					$this->search_engine->addItems(
						$items_type,
						$items
					);

					return Api_Status::$success;
				} catch ( \Exception $exception ) {
					$this->log->log( $exception );

					return Api_Status::$unknown_error;
				}
			}

			return Api_Status::$bad_request;
		} catch ( \Exception $exception ) {
			$this->log->log( $exception );

			return Api_Status::$unknown_error;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function remove_types() {
		if ( ! $this->search_engine ) {
			$this->log->log( 'Invalid search engine.' );

			return Api_Status::$invalid_search_engine;
		}

		// Doofinder API will throw an exception in case of invalid token
		// or something like that.
		try {
			$this->search_engine->deleteType( Settings::get_post_types_to_index() );

			return Api_Status::$success;
		} catch ( IndexingInProgress $exception ) {
			// This exception is thrown after we've sent request to delete a type
			// and the server is not finished processing it yet.
			// We'll have to retry the request in a moment.

			$this->log->log( $exception );

			return Api_Status::$indexing_in_progress;
		} catch ( \Exception $exception ) {
			$this->log->log( $exception );

			return Api_Status::$unknown_error;
		}
	}

	/**
	 * Retrieve search engine based on keys from settings.
	 *
	 * @return SearchEngine
	 * @throws \Exception
	 */
	private function get_search_engine() {
		$api_key = Settings::get_api_key();
		$hash    = Settings::get_search_engine_hash();
		if ( ! $api_key || ! $hash ) {
			return null;
		}

		/** @var Client $client */
		$client = new Throttle( new Client( $api_key ) );

		/** @var SearchEngine[] $search_engines */
		$search_engines = $client->getSearchEngines();

		// Find the search engine with the hash specified by
		// the user in the options.
		$selected_search_engine = null;
		foreach ( $search_engines as $search_engine ) {
			if ( $search_engine->hashid === $hash ) {
				return $search_engine;
			}
		}

		// We have not found the selected search engine.
		// Most likely user provided a wrong hash.
		return null;
	}

	/**
	 * Add a type to the index, if the type does not exist yet.
	 *
	 * @param string $item_type
	 */
	private function maybe_create_type( $item_type ) {
		$types = $this->search_engine->getTypes();

		if ( ! in_array( $item_type, $types ) ) {
			$this->search_engine->addType( $item_type );
		}
	}
}
