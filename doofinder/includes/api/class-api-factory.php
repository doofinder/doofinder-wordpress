<?php

namespace Doofinder\WP\Api;

defined( 'ABSPATH' ) or die();

/**
 * Generates instance of the class making API calls.
 *
 * There are few, because we want to be able to dump whatever's going to the API
 * to local log file during development, and send data to real API
 * when in production.
 */
class Api_Factory {

	/**
	 * Singleton instance of the class implementing API calls.
	 *
	 * @var Api_Wrapper
	 */
	private static $instance;

	/**
	 * Retrieve the instance of class making calls to the API.
	 *
	 * @return Api_Wrapper
	 */
	public static function get() {
		if ( self::$instance ) {
			return self::$instance;
		}

		// Use real API in production.
		// Dump data to local log file when in development.
		if ( WP_DEBUG ) {
			self::$instance = new Local_Dump();
		} else {
			self::$instance = new Doofinder_Api();
		}

		return self::$instance;
	}
}
