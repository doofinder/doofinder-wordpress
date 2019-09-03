<?php

namespace Doofinder\WP\Search;

use Doofinder\WP\Api\Search\Client;
use Doofinder\WP\Api\Search\Results;
use Doofinder\WP\Log;
use Doofinder\WP\Settings;

class Internal_Search {

	/**
	 * Singleton instance of this class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * We'll use it to log information if anything goes wrong.
	 *
	 * @var Log
	 */
	private $log;

	/**
	 * Retrieve (or create if one doesn't exist) a singleton
	 * instance of this class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		// Is Internal Search enabled?
		if ( ! Settings::is_internal_search_enabled() ) {
			return;
		}

		$this->log = new Log();

		// Do we have API Keys?
		if ( ! Settings::get_api_key() || ! Settings::get_search_engine_hash() ) {
			$this->log->log( 'API Keys not set.' );

			return;
		}

		$this->filter_search_query();
	}

	/**
	 * Hook into the query, and replace results with results from Doofinder Search.
	 */
	private function filter_search_query() {
		add_filter( 'posts_pre_query', function ( $posts, $query ) {
			// Only run it for search.
			if ( ! $query->is_search() ) {
				return $posts;
			}

			// Don't run it for WooCommerce product searches.
			if ( function_exists( 'is_shop' ) && is_shop() ) {
				return $posts;
			}

			if ( isset( $query->query['post_type'] ) && $query->query['post_type'] === 'product' ) {
				return $posts;
			}

			// Don't fetch default WP results.
			$search_query = $query->get( 's' );
			$query->set( 's', false );

			// Search Doofinder, and override the query.
			$search = new Doofinder_Search();
			if ( $search->is_ok() ) {
				// Determine how many posts per page.
				if ( $query->get( 'posts_per_page' ) ) {
					$per_page = (int) $query->get( 'posts_per_page' );
				} else {
					$per_page = (int) get_option( 'posts_per_page' );
				}

				// Which page of results to show?
				$page = 1;
				if ( $query->get( 'paged' ) ) {
					$page = (int) $query->get( 'paged' );
				}

				$search->search( $search_query, $page, $per_page );
			}

			// Doofinder found some results.
			if ( $search->get_ids() ) {
				$query->found_posts   = $search->get_total_posts();
				$query->max_num_pages = $search->get_total_pages();

				return $search->get_ids();
			}

			// Doofinder returned no results.
			// We should make sure that the query returns no results.
			// If we ignore this, or set empty array, ALL posts would be returned.
			$query->found_posts = 0;
			$query->max_num_pages = 0;
			return null;
		}, 10, 2 );
	}
}
