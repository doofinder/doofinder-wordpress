<?php
/**
 * Plugin Name: Doofinder
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version: 0.3.5
 * Author: Doofinder
 * Description: Integrate Doofinder Search in your WordPress website.
 *
 * @package WordPress
 */

namespace Doofinder\WP;

use Doofinder\WP\Search\Internal_Search;

defined( 'ABSPATH' ) or die;

if ( ! class_exists( '\Doofinder\WP\Doofinder_For_WordPress' ) ):

	/**
	 * Main Plugin Class
	 *
	 * @class Doofinder_For_WordPress
	 */
	class Doofinder_For_WordPress {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public static $version = '0.3.5';

		/**
		 * The only instance of Doofinder_For_WordPress
		 *
		 * @var Doofinder_For_WordPress
		 */
		protected static $_instance = null;

		/**
		 * Returns the only instance of Doofinder_For_WordPress
		 *
		 * @since 1.0.0
		 * @return Doofinder_For_WordPress
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/* Hacking is forbidden *******************************************************/

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wordpress-doofinder' ), '0.1' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wordpress-doofinder' ), '0.1' );
		}

		/* Initialization *************************************************************/

		/**
		 * Doofinder_For_WordPress constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$class = __CLASS__;

			// Enable ajax debugging if Whoops is installed.
			if ( class_exists( 'Whoops\Handler\JsonResponseHandler' ) ) {
				if ( \Whoops\Util\Misc::isAjaxRequest() ) {
					$jsonHandler = new \Whoops\Handler\JsonResponseHandler();
					$jsonHandler->setJsonApi( true );

					$run = new \Whoops\Run();
					$run->pushHandler( $jsonHandler );
				}
			}

			// Load classes on demand
			self::autoload( self::plugin_path() . 'includes/' );
			include_once 'lib/autoload.php';

			// Init admin functionalities
			if ( is_admin() ) {
				Thumbnail::prepare_thumbnail_size();
				Post::add_additional_settings();
				Post::register_webhooks();

				Settings::instance();
				Index_Interface::instance();
			}

			// Init frontend functionalities
			if ( ! is_admin() ) {
				JS_Layer::instance();
				Internal_Search::instance();
			}

			// Register all custom URLs
			add_action( 'init', function () use ( $class ) {
				call_user_func( array( $class, 'register_urls' ) );
			} );
		}

		/**
		 * Autoload custom classes. Folders represent namespaces (after the predefined plugin prefix),
		 * and files containing classes begin with "class-" prefix, so for example following file:
		 * example-folder/class-example.php
		 * Contains following class:
		 * Doofinder\WP\Example_Folder\Example
		 *
		 * @since 1.0.0
		 *
		 * @param string $dir Root directory of libraries (where to begin lookup).
		 */
		public static function autoload( $dir ) {
			$self = __CLASS__;
			spl_autoload_register( function ( $class ) use ( $self, $dir ) {
				$prefix = 'Doofinder\\WP\\';

				/*
				 * Check if the class uses the plugins namespace.
				 */
				$len = strlen( $prefix );
				if ( strncmp( $prefix, $class, $len ) !== 0 ) {
					return;
				}

				/*
				 * Class name after and path after the plugins prefix.
				 */
				$relative_class = substr( $class, $len );

				/*
				 * Class names and folders are lowercase and hyphen delimited.
				 */
				$relative_class = strtolower( str_replace( '_', '-', $relative_class ) );

				/*
				 * WordPress coding standards state that files containing classes should begin
				 * with 'class-' prefix. Also, we are looking specifically for .php files.
				 */
				$classes                          = explode( '\\', $relative_class );
				$last_element                     = end( $classes );
				$classes[ count( $classes ) - 1 ] = "class-$last_element.php";
				$filename                         = $dir . implode( '/', $classes );

				if ( file_exists( $filename ) ) {
					require_once $filename;
				}
			} );
		}

		/**
		 * Get the plugin path.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function plugin_path() {
			return plugin_dir_path( __FILE__ );
		}

		/**
		 * Get the plugin URL.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function plugin_url() {
			return plugin_dir_url( __FILE__ );
		}

		/**
		 * Initialize all functionalities that register custom URLs.
		 *
		 * @since 1.0.0
		 */
		public static function register_urls() {
			Platform_Confirmation::register();
		}

		/* Plugin activation and deactivation *****************************************/

		/**
		 * Activation Hook to configure routes and so on
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function plugin_enabled() {
			self::autoload( self::plugin_path() . 'includes/' );
			self::register_urls();
			flush_rewrite_rules();
		}

		/**
		 * Deactivation Hook to flush routes
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function plugin_disabled() {
			flush_rewrite_rules();
		}
	}

endif;

register_activation_hook( __FILE__, array( '\Doofinder\WP\Doofinder_For_WordPress', 'plugin_enabled' ) );
register_deactivation_hook( __FILE__, array( '\Doofinder\WP\Doofinder_For_WordPress', 'plugin_disabled' ) );

add_action( 'plugins_loaded', array( '\Doofinder\WP\Doofinder_For_WordPress', 'instance' ), 0 );
