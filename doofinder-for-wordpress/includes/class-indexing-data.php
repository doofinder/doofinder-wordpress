<?php

namespace Doofinder\WP;

/**
 * Wrapper for handling the information about the progress of indexing.
 *
 * The class automatically grabs data from the DB, falls back to defaults
 * if data is not present and makes sure only expected data can be saved
 * (so it will not allow to add data under any random index).
 *
 * Note that this class does not re-save the data automatically. The idea
 * is that we want to avoid unnecessary DB calls (in case someone wants to
 * just read the data, without modifying), so in order to save the modified
 * data "save" method should be called explicitly.
 */
class Indexing_Data {

	/**
	 * Singleton instance of this class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Name of the option that stores information about indexing the posts.
	 *
	 * @var string
	 */
	private static $option_name = 'doofinder_for_wp_posts_indexing';

	/**
	 * Default indexing data, if no data exists yet, or used to reset.
	 *
	 * @var array
	 */
	private static $default_data = array(
		// Possible values: new, processing, completed
		'status'             => 'new',

		// ID of the last post we sent to the API.
		'post_id'            => 0,

		// Post type currently being indexed.
		'post_type'          => '',

		// We remove the type from the index before sending posts
		// of a given type. This tracks what types we've already removed.
		'post_types_removed' => array(),

		// We want to keep track of what post types we are adding
		// after we remove them in order to avoid making additional requests
		// to the Doofinder API.
		'post_types_readded' => array()
	);

	/**
	 * All information regarding indexing the posts, from options.
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Retrieve (or create, if one does not exist yet) a singleton instance
	 * of this class.
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
		$data = get_option( self::$option_name );
		if ( $data ) {
			$this->data = array_merge( self::$default_data, $data );
		} else {
			$this->data = self::$default_data;
		}
	}

	/**
	 * Retrieve the specific piece of information pertaining to indexing the posts.
	 *
	 * @see Data_Index_Status::$data
	 *
	 * @param string $option_name
	 *
	 * @return mixed
	 */
	public function get( $option_name ) {
		if ( isset( $this->data[ $option_name ] ) ) {
			return $this->data[ $option_name ];
		}

		return null;
	}

	/**
	 * Check if the option contains a value.
	 *
	 * Contains as in - if the option is array this function checks
	 * if the array contains a given value.
	 *
	 * @param string $option_name
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public function has( $option_name, $value ) {
		if ( ! isset( $this->data[ $option_name ] ) ) {
			return false;
		}

		if ( is_array( $this->data[ $option_name ] ) ) {
			return in_array( $value, $this->data[ $option_name ] );
		}

		return $this->data[ $option_name ] === $value;
	}

	/**
	 * Set the value of the piece of information about indexing the posts.
	 *
	 * @param string $option_name
	 * @param mixed  $value
	 */
	public function set( $option_name, $value ) {
		// Only save value for the options that already exist.
		// That makes sure we don't write anything unexpected there.
		if ( ! isset( $this->data[ $option_name ] ) ) {
			return;
		}

		// If the option is an array - add the value.
		if ( is_array( $this->data[ $option_name ] ) ) {
			$this->data[ $option_name ][] = $value;

			return;
		}

		// Otherwise, just set the value.
		$this->data[ $option_name ] = $value;
	}

	/**
	 * Rested the indexing data to default.
	 */
	public function reset() {
		$this->data = self::$default_data;
	}

	/**
	 * Save the information about posts indexing the the DB.
	 */
	public function save() {
		update_option( self::$option_name, $this->data );
	}
}
