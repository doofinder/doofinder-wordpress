<?php

namespace Doofinder\WP;

use Doofinder\WP\Api\Update_On_Save_Api;
use Doofinder\WP\Log;
use Doofinder\WP\Multilanguage\Language_Plugin;
use Doofinder\WP\Multilanguage\Multilanguage;

defined( 'ABSPATH' ) or die;

/**
 * Handles the process of indexing the posts.
 *
 * Posts are indexed in batches - post type after post type, so each call
 * to index the posts will index only one batch, and move the "pointer"
 * (data about what has already been indexed, which is stored in DB).
 *
 * Core of the class functionality are methods "ajax_handler" and "index_posts"
 * which are responsible for indexing one batch of posts, rest are helpers
 * for building SQL queries to retrieve the data, etc.
 *
 * This class does not print any interface, just handles retrieving
 * posts from DB and sending them to Doofinder API.
 *
 */
class Update_On_Save_Index {

	/**
	 * Instance of class handling multilanguage environments.
	 *
	 * @var Language_Plugin
	 */
	private $language;

	/**
	 * Language selected for this operation.
	 *
	 * @var string
	 */
	private $current_language;

	/**
	 * Class handling API calls.
	 *
	 * @var Update_On_Save_Api
	 */
	private $api;

	/**
	 * Instance of a class used to log to a file.
	 *
	 * @var Log
	 */
	private $log;

	/**
	 * List of posts prepared to be indexed.
	 *
	 * @var array[]
	 */
	private $items = array();

	/**
	 * List with default post types.
	 *
	 * @var array
	 */
	private $post_types = array("product", "post", "page");

	public function __construct() {
		$this->language 			= Multilanguage::instance();
		$this->current_language 	= $this->language->get_active_language();
		$this->api					= new Update_On_Save_Api($this->current_language);
		$this->log 					= new Log( 'update_on_save_api.txt' );
	}

	/**
	 * Launches the Doofinder update on save process.
	 *
	 * This method triggers the update on save process by calling the `update_on_save()` method
	 * for both the "update" and "delete" actions.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function launch_doofinder_update_on_save() {
		$this->log->log('Launch Doofinder update on save');

		$this->update_on_save('update');

		$this->update_on_save('delete');
	}

	/**
	 * Perform the update on save operation for the specified action.
	 *
	 * @param string $action The action to perform (either "update" or "delete").
	 * @since 1.0.0
	 */
	public function update_on_save($action) {
		// Load the data that we'll use to fetch posts.
		$this->log->log('Update on save is enabled for these types of posts: ');
		$this->log->log($this->post_types);

		foreach ($this->post_types as $post_type) {
			$this->log->log('Posts ids to update for ' . $post_type . ': ');
			$posts_ids_to_update = $this->get_posts_ids_by_type_indexation($post_type, $action);
			$this->log->log($posts_ids_to_update);

			if (!empty($posts_ids_to_update)) {
				$url = $this->get_rest_url($post_type);

				// Call the function passing the post type name as a parameter
				$this->log->log('Load Posts ' . $post_type);
				$this->load_posts($posts_ids_to_update, $url);

				if (!empty($this->items) && $action === 'update') {
					$this->log->log('We send the request to UPDATE items with this data:');
					$this->api->updateBulk($post_type, $this->items);
					$this->items = array();
				} elseif (!empty($this->items) && $action === 'delete') {
					$this->log->log('We send the request to DELETE items with this data:');
					$this->api->deleteBulk($post_type, $this->items);
					$this->items = array();
				} else {
					$this->log->log('We have not been able to obtain data!');
				}
			} else {
				$this->log->log('There are no ids to update');
			}
		}
	}

	/**
	 * Get post IDs by type for indexation.
	 *
	 * @param string $post_type The type of posts to retrieve IDs for.
	 * @param string $action The action type ('update' or 'delete').
	 * @return array An array of post IDs.
	 * @since 1.0.0
	 */
	public function get_posts_ids_by_type_indexation($post_type, $action ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'doofinder_update_on_save';

        $query = "SELECT * FROM $table_name WHERE type_post = '$post_type' AND type_action = '$action'";

		$ids = $wpdb->get_results( $query, ARRAY_N );

		if ( ! $ids ) {
			return array();
		}

		return array_map( function ( $item ) {
			return $item[0];
		}, $ids );
	}

	/**
	 * Load posts from the WordPress API based on the provided IDs.
	 *
	 * @param array  $ids_items An array of post IDs.
	 * @param string $url       The URL of the WordPress API.
	 * @return void
	 * @since 1.0.0
	 */
	private function load_posts($ids_items, $url) {
		// Make the HTTP GET request to the WordPress API
		$query_params = array(
			'include'   => implode(',', $ids_items), // Convert the IDs list into a comma-separated string
			'per_page'  => 100, // Maximum number of products to fetch per request (adjust as needed)
		);

		$request_url = add_query_arg($query_params, $url); // Add the query parameters to the API URL

		$response = wp_remote_get($request_url);

		// Get the response code and response body
		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);

		// Check if the request was successful (response code 200)
		if ($response_code === 200) {
			$this->log->log('Request to obtain the IDs successful for ' . $request_url);
			
			// Decode the JSON response body into an array of posts
			$posts = json_decode($response_body, true);

			$this->log->log('These are the posts obtained:');
			$this->log->log(print_r($posts, true));

			$this->items = $posts;
		} else {
			// The request was not successful, handle the error
			$this->log->log('Error in IDs request:');
			$this->log->log(print_r($response_code, true));

			$this->items = array();
		}
	}

	/**
	 * Get the REST URL based on the provided post type.
	 *
	 * @param string $type The post type.
	 * @return string The REST URL.
	 * @since 1.0.0
	 */
	private function get_rest_url($type) {
		switch ($type) {
			case "product":
				$rest_url = rest_url('wp-json/wc/v3/products?_embed');
				break;
			case "page":
				$rest_url = rest_url('wp/v2/pages?_embed');
				break;
			default:
				$rest_url = rest_url('wp/v2/posts?_embed');
				break;
		}

		return $rest_url;
	}

}