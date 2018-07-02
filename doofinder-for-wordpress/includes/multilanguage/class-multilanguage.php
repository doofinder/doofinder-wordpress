<?php

namespace Doofinder\WP\Multilanguage;

class Multilanguage {

	/**
	 * Singleton instance of class that implements
	 * Language_Plugin interface.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Create (or retrieve, if already exists) the singleton
	 * instance of class that implements Language_Plugin
	 * interface.
	 *
	 * @return Language_Plugin
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			if ( class_exists( 'SitePress' ) ) {
				self::$instance = new WPML();

				return self::$instance;
			}

			if ( defined( 'POLYLANG_BASENAME' ) ) {
				self::$instance = new Polylang();

				return self::$instance;
			}
		}

		// Still no instance?
		// That means we have no Multilanguage plugins installed.
		if ( ! self::$instance ) {
			self::$instance = new No_Language_Plugin();
		}

		return self::$instance;
	}
}
