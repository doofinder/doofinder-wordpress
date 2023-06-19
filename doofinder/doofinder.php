<?php

/**
 * Plugin Name: Doofinder
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version: 0.5.4
 * Author: Doofinder
 * Description: Integrate Doofinder Search in your WordPress website.
 *
 * @package WordPress
 */

namespace Doofinder\WP;

use WP_REST_Response;

defined('ABSPATH') or die;

if (!class_exists('\Doofinder\WP\Doofinder_For_WordPress')) :

    /**
     * Main Plugin Class
     *
     * @class Doofinder_For_WordPress
     */
    class Doofinder_For_WordPress
    {

        /**
         * Plugin version.
         *
         * @var string
         */
        public static $version = '0.5.4';

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
        public static function instance()
        {
            if (is_null(self::$_instance)) {
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
        public function __clone()
        {
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'wordpress-doofinder'), '0.1');
        }

        /**
         * Unserializing instances of this class is forbidden.
         *
         * @since 1.0.0
         */
        public function __wakeup()
        {
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'wordpress-doofinder'), '0.1');
        }

        /* Initialization *************************************************************/

        /**
         * Doofinder_For_WordPress constructor.
         *
         * @since 1.0.0
         */
        public function __construct()
        {
            $class = __CLASS__;

            // Load classes on demand
            self::autoload(self::plugin_path() . 'includes/');
            include_once 'lib/autoload.php';

            // Init admin functionalities
            if (is_admin()) {
                Thumbnail::prepare_thumbnail_size();
                Post::add_additional_settings();
                Settings::instance();
                if (Setup_Wizard::should_activate()) {
                    Setup_Wizard::activate(true);
                }

                Setup_Wizard::instance();
                Update_On_Save::register_hooks();

                self::register_ajax_action();
                self::register_admin_scripts_and_styles();

                // Try to migrate settings if possible and necessary
                if (Setup_Wizard::should_migrate()) {
                    Migration::migrate();
                }
            }

            // Init frontend functionalities
            if (!is_admin()) {
                JS_Layer::instance();
            }

            add_action('init', function () use ($class) {
                // Register all custom URLs
                call_user_func(array($class, 'register_urls'));
            });

            self::initialize_rest_endpoints();
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
        public static function autoload($dir)
        {
            $self = __CLASS__;
            spl_autoload_register(function ($class) use ($self, $dir) {
                $prefix = 'Doofinder\\WP\\';

                /*
				 * Check if the class uses the plugins namespace.
				 */
                $len = strlen($prefix);
                if (strncmp($prefix, $class, $len) !== 0) {
                    return;
                }

                /*
				 * Class name after and path after the plugins prefix.
				 */
                $relative_class = substr($class, $len);

                /*
				 * Class names and folders are lowercase and hyphen delimited.
				 */
                $relative_class = strtolower(str_replace('_', '-', $relative_class));

                /*
				 * WordPress coding standards state that files containing classes should begin
				 * with 'class-' prefix. Also, we are looking specifically for .php files.
				 */
                $classes                          = explode('\\', $relative_class);
                $last_element                     = end($classes);
                $classes[count($classes) - 1] = "class-$last_element.php";
                $filename                         = $dir . implode('/', $classes);

                if (file_exists($filename)) {
                    require_once $filename;
                }
            });
        }

        /**
         * Get the plugin path.
         *
         * @since 1.0.0
         * @return string
         */
        public static function plugin_path()
        {
            return plugin_dir_path(__FILE__);
        }

        /**
         * Get the plugin URL.
         *
         * @since 1.0.0
         * @return string
         */
        public static function plugin_url()
        {
            return plugin_dir_url(__FILE__);
        }

        /**
         * Initialize all functionalities that register custom URLs.
         *
         * @since 1.0.0
         */
        public static function register_urls()
        {
            Platform_Confirmation::register();
        }

        /* Plugin activation and deactivation *****************************************/

        /**
         * Activation Hook to configure routes and so on
         *
         * @since 1.0.0
         * @return void
         */
        public static function plugin_enabled()
        {
            self::autoload(self::plugin_path() . 'includes/');
            self::register_urls();
            flush_rewrite_rules();

            Update_On_Save::create_update_on_save_db();

            $log = new Log();
            $log->log('Plugin enabled');

            if (Setup_Wizard::should_activate()) {
                Setup_Wizard::activate(true);
            }
        }

        /**
         * Deactivation Hook to flush routes
         *
         * @since 1.0.0
         * @return void
         */
        public static function plugin_disabled()
        {
            flush_rewrite_rules();
            Update_On_Save::clean_update_on_save_db();
            Update_On_Save::delete_update_on_save_db();
        }

        /**
         * This function runs when WordPress completes its upgrade process
         * It iterates through each plugin updated to see if ours is included
         *
         * @param array $upgrader_object
         * @param array $options
         */
        public static function upgrader_process_complete($upgrader_object, $options)
        {
            $log = new Log();
            $log->log('upgrader_process - start');
            // The path to our plugin's main file
            $our_plugin = plugin_basename(__FILE__);

            $log->log($our_plugin);
            $log->log($options);

            // If an update has taken place and the updated type is plugins and the plugins element exists
            if ($options['action'] == 'update' && $options['type'] == 'plugin') {

                $log->log('upgrader_process - updating plugin');

                if (isset($options['plugins'])) {
                    $plugins = $options['plugins'];
                } elseif (isset($options['plugin'])) {
                    $plugins = [$options['plugin']];
                }

                $log->log($plugins);
            }
        }

        public static function register_admin_scripts_and_styles()
        {
            add_action('admin_enqueue_scripts', function () {
                wp_enqueue_script('doofinder-admin-js', plugins_url('assets/js/admin.js', __FILE__));
                wp_localize_script('doofinder-admin-js', 'Doofinder', [
                    'show_indexing_notice' => Setup_Wizard::should_show_indexing_notice() ? 'true' : 'false'
                ]);

                // CSS
                wp_enqueue_style('doofinder-admin-css', Doofinder_For_WordPress::plugin_url() . '/assets/css/admin.css');
            });
        }

        public static function initialize_rest_endpoints()
        {
            add_action('rest_api_init', function () {
                register_rest_route('doofinder/v1', '/indexation-status', array(
                    'methods' => 'GET',
                    'callback' => function (\WP_REST_Request $request) {
                        if ($request->get_param('token') != Settings::get_api_key()) {
                            return new WP_REST_Response(
                                [
                                    'status' => 401,
                                    'response' => "Invalid token"
                                ],
                                401
                            );
                        }

                        //Hide the indexing notice
                        Setup_Wizard::dismiss_indexing_notice();
                        Settings::set_indexing_status('processed');

                        return new WP_REST_Response(
                            [
                                'status' => 200,
                                'indexing_status' => Settings::get_indexing_status(),
                                'response' => "Indexing status updated"
                            ]
                        );
                    },
                    'permission_callback' => '__return_true'
                ));
            });
        }

        /**
         * Register an ajax action that processes wizard step 2 and creates search engines.
         *
         *
         * @since 1.0.0
         */
        private static function register_ajax_action()
        {
            add_action('wp_ajax_doofinder_check_indexing_status', function () {
                wp_send_json([
                    'status' => Settings::get_indexing_status()
                ]);
                exit;
            });
        }
    }

endif;

register_activation_hook(__FILE__, array('\Doofinder\WP\Doofinder_For_WordPress', 'plugin_enabled'));
register_deactivation_hook(__FILE__, array('\Doofinder\WP\Doofinder_For_WordPress', 'plugin_disabled'));

add_action('plugins_loaded', array('\Doofinder\WP\Doofinder_For_WordPress', 'instance'), 0);

add_action('upgrader_process_complete', array('\Doofinder\WP\Doofinder_For_WordPress', 'upgrader_process_complete'), 10, 2);
