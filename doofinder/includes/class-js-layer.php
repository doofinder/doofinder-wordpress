<?php

namespace Doofinder\WP;

class JS_Layer {

	/**
	 * Singleton instance of this class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * @var Log
	 */
	private $log;

	/**
	 * Retrieve (or create, if one does not exist) a singleton
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
		if (
			! Settings::is_js_layer_enabled() ||
			(
				! Settings::get_js_layer() &&
				! Settings::is_js_layer_from_doofinder_enabled()
			)
		) {
			return;
		}

		$this->log = new Log();

		$this->insert_js_layer();
	}

	/**
	 * Insert the code of the JS Layer to HTML.
	 */
	private function insert_js_layer() {
		add_action( 'wp_footer', function () {
			if ( Settings::is_js_layer_from_doofinder_enabled() ) {
				$this->insert_js_layer_from_doofinder();

				return;
			}

			$this->insert_js_layer_from_options();
		} );
	}

	/**
	 * Output JS Layer script pasted by the user in the options.
	 */
	private function insert_js_layer_from_options() {
		echo Settings::get_js_layer();
	}

	/**
	 * Output a script tag fetching JS Layer script directly
	 * from Doofinder server. This is based on the API Key
	 * and hash.
	 */
	private function insert_js_layer_from_doofinder() {
		$api_key = Settings::get_api_key();
		$hash    = Settings::get_search_engine_hash();

		if ( ! $api_key || ! $hash ) {
			return;
		}

		if ( ! preg_match( '/(.+?)-/', $api_key, $matches ) ) {
			$this->log->log( 'Could not extract zone from API Key.' );

			return;
		}

		echo <<<HTML
<script type="text/javascript" src="//{$matches[1]}-search.doofinder.com/5/script/{$hash}.js"></script>
HTML;
	}
}
