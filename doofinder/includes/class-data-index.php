<?php

namespace Doofinder\WP;

use Doofinder\WP\Log;
use Doofinder\WP\Api\Api_Factory;
use Doofinder\WP\Api\Api_Wrapper;
use Doofinder\WP\Api\Api_Status;
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
 * @see Data_Index::ajax_handler()
 * @see Data_Index::index_posts()
 * @see Index_Interface
 * @see Indexing_Data
 */
class Data_Index {

	/**
	 * Number of posts per page.
	 *
	 * @var int
	 */
	private static $posts_per_page = 100;

	/**
	 * Instance of class handling multilanguage environments.
	 *
	 * @var Language_Plugin
	 */
	private $language;

	/**
	 * Data containing progress of indexing.
	 *
	 * @var Indexing_Data
	 */
	private $indexing_data;

	/**
	 * Class handling API calls.
	 *
	 * @var Api_Wrapper
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
	 * List of posts ids fetched from DB.
	 *
	 * @var array
	 */
	private $posts_ids;

	/**
	 * List of posts objects fetched from DB.
	 *
	 * @var \WP_Post[]
	 */
	private $posts;

	/**
	 * Number of posts fetched from DB.
	 *
	 * @var int
	 */
	private $post_count = 0;

	/**
	 * Posts meta.
	 *
	 * @var array
	 */
	private $posts_meta;

	/**
	 * List with all post types.
	 *
	 * @var array
	 */
	private $post_types;

	public function __construct() {
		$this->language      = Multilanguage::instance();
		$this->indexing_data = Indexing_Data::instance();
		$this->api           = Api_Factory::get();

		$this->log = new Log( 'api.txt' );

		$this->log->log( '-------------------------------------------------------------------------------------------------------------------------------------' );
		$this->log->log( '-------------------------------------------------------------------------------------------------------------------------------------' );
		$this->log->log( '-------------------------------------------------------------------------------------------------------------------------------------' );
	}

	public function ajax_handler() {
		$status = $this->indexing_data->get( 'status' );

		// If the indexing has been completed we are reindexing.
		// Reset the status of indexing.
		if ( $status === 'completed' ) {
			$this->log->log( 'Ajax Handler - Reset indexing data ' );
			$this->indexing_data->reset();
		}

		// Index the posts.
		if ( $this->index_posts() ) {
			$this->ajax_response( true, 'Wrapping up...' );

			return;
		}

		$post_type = $this->get_post_type_name( $this->indexing_data->get( 'post_type' ) );
		$this->ajax_response( false, "Indexing \"$post_type\" type contents..." );
	}

	/**
	 * Get posts from DB, send via API, and return status
	 * (if the process of indexing has been completed) as JSON.
	 *
	 * @return bool True if the indexing has finished.
	 * @since 1.0.0
	 */
	public function index_posts() {
		// $this->log->log( 'Maybe Remove Posts' );
		// $this->maybe_remove_posts();

		// Load the data that we'll use to fetch posts.
		$this->log->log( 'Load Post Types' );
		$this->load_post_types();
		$this->log->log( $this->post_types );

		// This function also removes the current post type.
		// This is done because "load_posts_id" can skip a post type
		// if it contains 0 posts, but we still need to remove
		// the post type, or the old posts will remain in the DB.
		$this->log->log( 'Load Post IDs' );
		$this->load_posts_ids();
		$this->log->log( 'Post IDs : ' );
		$this->log->log( $this->posts_ids );

		// We fetch next batch of post IDs, from the current post type,
		// but advance to the next post type, if there are no more posts.
		// If we hit 0 posts at this point that means we checked current
		// post type, advanced to the next one, there was none, which
		// means we are done.
		if ( $this->post_count === 0 ) {
			$this->call_replace_index();
			$this->indexing_data->set( 'status', 'completed' );

			return true;
		}

		// Load actual posts and their data.
		$this->load_posts();
		$this->load_posts_meta();

		// Prepare posts to be sent to the API.
		$this->generate_items();

		// Send posts to the API.
		// At this point if the posts are sent successfully
		// we'll advance the "pointer" pointing to the last
		// indexed post.
		if ( $this->items ) {
			$sent_to_api = $this->api->send_batch( $this->indexing_data->get( 'post_type' ), $this->items, $this->indexing_data->get( 'lang' ) );

			if ( $sent_to_api !== Api_Status::$success ) {
				$post_type = $this->get_post_type_name( $this->indexing_data->get( 'post_type' ) );

				$message = __( "Indexing \"$post_type\" type contents...", 'doofinder_for_wp' );
				if ( $sent_to_api === Api_Status::$indexing_in_progress ) {
					$message = __( "Deleting \"$post_type\" type...", 'doofinder_for_wp' );
				}

				$this->ajax_response_error( array(
					'status'  => $sent_to_api,
					'message' => $message,
				) );
			}
		}

		// We land here in two cases:
		// 1. We had items and sent them to API successfully. If API
		//    call fails the script will terminate.
		// 2. There were no items. This happens when for example we hit
		//    a batch of posts that all have settings preventing them
		//    from being indexed.
		$this->push_pointer_forwards();

		return false;
	}

	/**
	 * Remove all post types, but only once, at the beginning of indexing process.
	 * No need to use in api v2.0
	 */
	// private function maybe_remove_posts() {
	// 	if ( $this->indexing_data->get( 'status' ) === 'processing' ) {
	// 		return;
	// 	}

	// 	$types_removed = $this->api->remove_types();
	// 	$this->indexing_data->set( 'status', 'processing' );

	// 	if ( $types_removed !== Api_Status::$success ) {
	// 		$this->ajax_response_error( array(
	// 			'status'  => $types_removed,
	// 			'message' => __( 'Deleting objects...', 'doofinder_for_wp' ),
	// 		) );
	// 	}

	// 	$this->indexing_data->set( 'post_types_removed', Settings::get_post_types_to_index() );
	// }

	/**
	 * Load post types from DB, and set current post type if is not defined.
	 *
	 * @since 1.0.0
	 */
	private function load_post_types() {
		$post_types       = Post_Types::instance();
		$this->post_types = $post_types->get_indexable();

		// if we start indexing, then post type is not set, so we get first post type from list
		// Replace invalid characters with valid ones
		if ( ! $this->indexing_data->get( 'post_type' ) ) {
			$this->indexing_data->set( 'post_type', str_replace('-', '_', $this->post_types[0]) );
		}
	}

	/**
	 * Load posts ids from DB.
	 *
	 * @since 1.0.0
	 */
	private function load_posts_ids() {
		$last_id        = $this->indexing_data->get( 'post_id' );
		$post_type      = $this->indexing_data->get( 'post_type' );
		$posts_per_page = self::$posts_per_page;

		$this->posts_ids = $this->language->get_posts_ids( $this->language->get_active_language(), $post_type, $last_id, $posts_per_page );

		$this->post_count = count( $this->posts_ids );

		if ( $this->post_count === 0 ) {
			if ( $this->check_next_post_type() ) {
				$this->load_posts_ids();
			}
		}
	}

	/**
	 * Load posts from DB.
	 *
	 * @since 1.0.0
	 */
	private function load_posts() {
		// Whilst the default WP_Query post_status is "publish",
		// attachments have a default post_status of "inherit".
		// This means no attachments will be returned unless we
		// also explicitly set post_status to "inherit" or "any".
		if ( $this->indexing_data->get( 'post_type' ) === 'attachment' ) {
			$post_status = 'inherit';
		} else {
			$post_status = 'publish';
		}

		$args = array(
			'post_type'   => $this->indexing_data->get( 'post_type' ),
			'post__in'    => $this->posts_ids,
			'post_status' => $post_status,

			'posts_per_page' => self::$posts_per_page,
			'orderby'        => 'ID',
			'order'          => 'ASC',

			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		$query = new \WP_Query( $args );

		$this->posts = $query->posts;
	}

	/**
	 * Load posts meta from DB.
	 *
	 * @since 1.0.0
	 */
	private function load_posts_meta() {
		global $wpdb;
		$posts_ids_list = implode( ', ', $this->posts_ids );

		$visibility_meta  = Post::$options['visibility']['meta_name'];
		$yoast_visibility = Post::$options['yoast_visibility']['meta_name'];
		$query            = "
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE $wpdb->postmeta.post_id IN ($posts_ids_list)
			AND (
              $wpdb->postmeta.meta_key NOT LIKE '\_%' OR
              $wpdb->postmeta.meta_key = '$visibility_meta' OR
              $wpdb->postmeta.meta_key = '$yoast_visibility'
            )
			ORDER BY $wpdb->postmeta.post_id
		 ";

		$this->posts_meta = $wpdb->get_results( $query, OBJECT );
	}

	/**
	 * Generate items to be indexed via API.
	 *
	 * @since 1.0.0
	 */
	private function generate_items() {
		foreach ( $this->posts as $post ) {
			// We have meta of all posts.
			// Get only meta for the current post.
			$meta = array_filter( $this->posts_meta, function ( $post_meta ) use ( $post ) {
				if ( (int) $post->ID !== (int) $post_meta->post_id ) {
					return false;
				}

				if ( ! isset( $post_meta->meta_key ) || ! isset( $post_meta->meta_value ) ) {
					return false;
				}

				return true;
			} );

			$doofinder_post = new Post( $post, $meta );
			if ( $doofinder_post->is_indexable() ) {
				$this->items[] = $doofinder_post->format_for_api();
			}
		}
	}

	/**
	 * Advance the pointer (last indexed post) forward, to the last
	 * post we indexed in this batch.
	 *
	 * This should be called only if the posts are successfully sent
	 * to the API, because if API call fails, and we move forward
	 * despite that, then we will miss some posts.
	 */
	private function push_pointer_forwards() {
		if ( ! $this->posts_ids ) {
			return;
		}

		$this->indexing_data->set( 'post_id', $this->posts_ids[ count( $this->posts_ids ) - 1 ] );
	}

	/**
	 * Wrapper function for check and set next item from the container list.
	 * If next item does not exist, then simple return false, otherwise true.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function check_next_($item, $container, $get_new_api = false, $skip_replace_index = false)
	{
		$current_item_index = array_search($this->indexing_data->get($item), $container);
		$next_item_index    = $current_item_index + 1;

		if (isset($container[$next_item_index]) && $container[$next_item_index]) {

			if (!$skip_replace_index) {
				// We are done with this batch, replace temp index
				$this->log->log('Check Next ' . $item . '  - Call replace Index');
				if ($this->api) {
					$this->call_replace_index($get_new_api);
				}
			}

			$this->indexing_data->set($item, $container[$next_item_index]);
			$this->indexing_data->set('post_id', 0);

			$current_progress = $this->indexing_data->get('current_progress');
			$this->log->log('Check Next ' . $item . ' - Current progress: ', $current_progress);

			$this->indexing_data->set('processed_posts_count', $current_progress);

			return true;
		}

		return false;
	}

	/**
	 * Check and set next post type from the post types list.
	 * If next post type does not exist, then simple return false, otherwise true.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	private function check_next_post_type($skip_replace_index = false) {
		return $this->check_next_('post_type', $this->post_types, false, $skip_replace_index);

	}

	/**
	 * Prepare ajax response.
	 *
	 * @param bool $completed Status of indexing posts.
	 * @param string $message Additional message to pass to front.
	 *
	 * @since 1.0.0
	 *
	 */
	private function ajax_response( $completed, $message = '' ) {
		// We're about to call "die", so we need to make sure our data
		// gets saved. Originally this was in the destructor but there
		// was an error - when sometimes WP cache crashed destructor
		// was not called, and the pointer information was not saved
		// in the DB as a result.
		$this->indexing_data->save();

		$content = array(
			'completed' => $completed,
			'progress'  => $this->calculate_progress(),
		);

		if ( $message ) {
			$content['message'] = $message;
		}

		wp_send_json_success( $content );
	}

	/**
	 * Whenever we send response to the frontend the script terminates
	 * so if we don't save the indexing data it will be lost.
	 *
	 * @param array $args Arguments for "wp_send_json_error".
	 */
	private function ajax_response_error( $args = array() ) {
		// We're about to call "die", so we need to make sure our data
		// gets saved. Originally this was in the destructor but there
		// was an error - when sometimes WP cache crashed destructor
		// was not called, and the pointer information was not saved
		// in the DB as a result.
		$this->indexing_data->save();

		wp_send_json_error( $args );
	}

	/**
	 * Calculate the percentage of images already processed.
	 *
	 * We send posts to API post type after post type. Therefore we cannot
	 * find out how many posts we already sent just by looking at the ID,
	 * posts from post type B might come before posts from post type A.
	 *
	 * So the way to find out how many posts were processed already
	 * is to find out:
	 * - Total number of posts withing post types that were already
	 *   completely processed.
	 * - How many posts from the post type currently being processed
	 *   were already sent (all posts from the current post type up
	 *   to and including the ID of the last processed post).
	 *
	 * This function builds one SQL query that contains three COUNTs -
	 * the two mention above plus the count of all posts from all
	 * post types that we are indexing, and calculates percentage
	 * based on that.
	 *
	 * @return float Percentage of already processed posts.
	 */
	private function calculate_progress() {
		global $wpdb;

		// Base query - count of all posts of all supported post types.
		// Essentially - how many total posts are there to index.
		$post_types_list = $this->make_sql_list( $this->post_types );

		$query = "
			SELECT
			(
				SELECT COUNT(*)
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type IN ($post_types_list)
			)
			AS 'all_posts'
		";

		// If there are any post types that we already fully indexed,
		// count posts from them.
		$indexed_post_types = array();

		// Take all post types that are before the post type we're
		// currently working on.
		foreach ( $this->post_types as $post_type ) {
			if ( $post_type === $this->indexing_data->get( 'post_type' ) ) {
				break;
			}

			$indexed_post_types[] = $post_type;
		}

		// Ok, if we have already indexed post types, add them to query.
		if ( $indexed_post_types ) {
			$indexed_post_types_list = $this->make_sql_list( $indexed_post_types );

			$query .= "
				, -- Separates this select from previous
				(
					SELECT
					COUNT(*)
					FROM $wpdb->posts
					WHERE $wpdb->posts.post_type IN ($indexed_post_types_list)
				)
			    AS 'already_processed'
			";
		}

		// Add posts from the currently processed post type.
		if ( $this->indexing_data->get( 'post_type' ) ) {
			$post_type = $this->indexing_data->get( 'post_type' );
			$last_id   = $this->indexing_data->get( 'post_id' );

			$query .= "
				, -- Separates this select from previous
				(
					SELECT COUNT(*)
					FROM $wpdb->posts
					WHERE $wpdb->posts.post_type = '$post_type'
					AND $wpdb->posts.ID <= $last_id
				)
				AS 'current_progress'
			";
		}

		// Check if returned data is valid. It should be array containing one element
		// (query returns one row of results)
		$result = $wpdb->get_results( $query );
		if ( ! $result || ! $result[0] ) {
			return 0;
		}

		$result = $result[0];

		// Calculate percentage of posts processed.
		// This will be - percentage of  all posts from already processed post types
		// plus all the posts from post type currently being processed
		// that were already indexed.
		$processed_posts = 0;
		if ( isset( $result->already_processed ) ) {
			$processed_posts += $result->already_processed;
		}

		if ( isset( $result->current_progress ) ) {
			$processed_posts += $result->current_progress;
		}

		return ( $processed_posts / $result->all_posts ) * 100;
	}

	/**
	 * Convert an array of string values into a list usable in an SQL query,
	 * so - items will be comma separated, and wrapped in "'", e.g.
	 * 'one','two','three'
	 *
	 * @param string[] $items
	 *
	 * @return string
	 */
	private function make_sql_list( array $items ) {
		return implode( ',', array_map( function ( $item ) {
			return "'$item'";
		}, $items ) );
	}

	/**
	 * Get the real, public name (label) of the post type with a given slug.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	private function get_post_type_name( $slug ) {
		$post_type = get_post_type_object( $slug );
		if ( ! $post_type ) {
			return $slug;
		}

		return $post_type->labels->singular_name;
	}

	/**
	 * We are done so replace real index with temp one. Call replace temp index API method.
	 *
	 */
	private function call_replace_index( $get_api = false ) {

		if ( $get_api ) {
			$language = $this->indexing_data->get( 'lang' );
			//$this->log->log( 'Call Replace Index  - lang: ' . $language );
			$this->log->log( 'Call Replace Index  - Get API Client Instance' );
			$this->api = Api_Factory::get( $language );
		}
		// Replace invalid characters with valid ones
		$post_type = str_replace('-', '_', $this->indexing_data->get( 'post_type' ));

		$api_response = $this->api->replace_index( $post_type );

		$this->log->log( $api_response );

		if ( $api_response !== Api_Status::$success ) {

			$message = __( "Replacing Index \"$post_type\" with temporary one.", 'woocommerce-doofinder' );

			// if ( $sent_to_api === Api_Status::$indexing_in_progress ) {
			// 	$message = __( "Deleting \"$post_type\" type...", 'woocommerce-doofinder' );
			// }

			$this->ajax_response_error( array(
				'status'  => $api_response,
				'message' => $message,
			) );
		}
	}
}
