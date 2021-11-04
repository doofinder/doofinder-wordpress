<?php

namespace Doofinder\WP\Api;

use Doofinder\WP\Indexing_Data;
use Doofinder\WP\Log;
use Doofinder\WP\Multilanguage\Language_Plugin;
use Doofinder\WP\Multilanguage\Multilanguage;

defined( 'ABSPATH' ) or die();

class Local_Dump implements Api_Wrapper {

	/**
	 * If set to true the mock API call will fail.
	 *
	 * This allows testing failed API responses.
	 *
	 * @var bool
	 */
	private $should_fail = false;

	/**
	 * Instance of the class handling multilanguage.
	 *
	 * @var Language_Plugin
	 */
	private $language;

	/**
	 * Instance of a class used to log to a file.
	 *
	 * @var Log
	 */
	private $log;

	public function __construct() {
		$this->language = Multilanguage::instance();
		$this->log = new Log( 'api.txt' );
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
		// Fail the API call if we want to test something.
		if ( $this->should_fail ) {
			return Api_Status::$unknown_error;
		}

		$status = 'Updating a post';
		if ( $this->language->get_active_language() ) {
			$status .= ' - ' . $this->language->get_active_language();
		}

		$this->log->log( $status );
		$this->log->log( $item_type );
		$this->log->log( $id );
		$this->log->log( $data );

		return Api_Status::$success;
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
		// Fail the API call if we want to test something.
		if ( $this->should_fail ) {
			return Api_Status::$unknown_error;
		}

		$status = 'Removing post from index';
		if ( $this->language->get_active_language() ) {
			$status .= ' - ' . $this->language->get_active_language();
		}

		$this->log->log( $status );
		$this->log->log( $item_type );
		$this->log->log( $id );

		return Api_Status::$success;
	}

	/**
	 * @inheritdoc
	 */
	public function send_batch( $items_type, array $items ) {
		// Fail the API call if we want to test something.
		if ( $this->should_fail ) {
			return Api_Status::$unknown_error;
		}

		// Fake updating the status.
		$indexing_data = Indexing_Data::instance();
		if ( ! $indexing_data->has( 'post_types_readded', $items_type ) ) {
			$indexing_data->set( 'post_types_readded', $items_type );
		}

		$this->log->log( $items_type );
		$this->log->log( $items );

		return Api_Status::$success;
	}

	/**
	 * @inheritdoc
	 */
	public function remove_types() {
		// Fail the API call if we want to test something.
		if ( $this->should_fail ) {
			return Api_Status::$unknown_error;
		}

		$this->log->log('- Removing all post types -');

		return Api_Status::$success;
	}

	/**
	 * @inheritdoc
	 */
	public function replace_index( $index_name ) {
		// Fail the API call if we want to test something.
		if ( $this->should_fail ) {
			return Api_Status::$unknown_error;
		}

		$this->log->log( 'Replace Index' );

		$indexing_data = Indexing_Data::instance();

		// Clear internal status of the temp index
		$indexing_data->set( 'temp_index', [] );

		$this->log->log('- Replacing real index with temp index -');
		$this->log->log('- Deleting temp index -');

		return Api_Status::$success;
	}
}
