<?php

namespace Doofinder\WP;

use Doofinder\WP\Multilanguage\Language_Plugin;
use Doofinder\WP\Multilanguage\Multilanguage;

use Doofinder\WP\Settings\Accessors;
use Doofinder\WP\Settings\Register_Settings;
use Doofinder\WP\Settings\Renderers;
use Doofinder\WP\Settings\Helpers;

defined( 'ABSPATH' ) or die;

class Settings {
    use Accessors;
    use Register_Settings;
    use Renderers;
    use Helpers;

	/**
	 * Slug of the top-level menu page.
	 *
	 * Other classes can use this to register submenus.
	 *
	 * @var string
	 */
	public static $top_level_menu = 'doofinder_for_wp';

	/**
	 * Array of tab settings, indexed by the id of the tag (the GET variable
	 * representing given tab). Values contain:
	 * label - Displayed in the tab.
	 * fields_cb - Function registering settings under given tab.
	 *
	 * No default, because the names of the tabs need to be translated,
	 * so we need to run them through translating functions. This will
	 * be then set in the constructor.
	 *
	 * @var array
	 */
	private static $tabs;

	/**
	 * The only instance of Settings
	 *
	 * @var Settings
	 */
	private static $_instance = null;

	/**
	 * Instance of the class handling multilanguage.
	 *
	 * @var Language_Plugin
	 */
	private $language;

	/**
	 * Returns the only instance of Settings
	 *
	 * @since 1.0.0
	 * @return Settings
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Settings constructor.
	 */
	private function __construct() {
		$this->language = Multilanguage::instance();

		self::$tabs = array(
			'authentication' => array(
				'label'     => __( 'Authentication', 'doofinder_for_wp' ),
				'fields_cb' => 'add_authentication_settings'
			),
			'search'         => array(
				'label'     => __( 'Search Settings', 'doofinder_for_wp' ),
				'fields_cb' => 'add_search_settings'
			)
		);

		$this->add_plugin_settings();
		$this->add_settings_page();
		$this->add_admin_scripts();
	}

	/**
	 * Register styles used by the Doofinder top level page.
	 */
	private function add_admin_scripts() {
		add_action( 'admin_enqueue_scripts', function () {
			// Don't add these scripts on pages other than the Doofinder top level page.
			// Other pages don't use them.
			$screen = get_current_screen();
			if ( $screen->id !== 'toplevel_page_doofinder_for_wp' ) {
				return;
			}

			// CSS
			wp_enqueue_style(
				'doofinder-for-wp-styles',
				Doofinder_For_WordPress::plugin_url() . '/assets/css/admin.css'
			);
		} );
	}
	

    /**
	 * Determine if the update on save is enabled.
	 *
	 * Just an alias for "get_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @return bool
	 */
	public static function is_update_on_save_enabled()
	{
		$option = get_option('doofinder_for_wp_update_on_save', 'every_day');
		return  $option != 'every_day';
	}
}
