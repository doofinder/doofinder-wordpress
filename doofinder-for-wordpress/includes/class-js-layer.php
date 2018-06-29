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
		if ( ! Settings::is_js_layer_enabled() || ! Settings::get_js_layer() ) {
			return;
		}

		$this->insert_js_layer();
	}

	/**
	 * Insert the code of the JS Layer to HTML.
	 */
	private function insert_js_layer() {
		add_action( 'wp_footer', function () {
			echo Settings::get_js_layer();
		} );
	}
}
