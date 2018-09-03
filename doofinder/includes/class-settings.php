<?php

namespace Doofinder\WP;

use Doofinder\WP\Multilanguage\Language_Plugin;
use Doofinder\WP\Multilanguage\Multilanguage;
use Doofinder\WP\Multilanguage\No_Language_Plugin;

defined( 'ABSPATH' ) or die;

class Settings {

	/**
	 * Slug of the top-level menu page.
	 *
	 * Other classes can use this to register submenus.
	 *
	 * @var string
	 */
	public static $top_level_menu = 'doofinder_for_wp';

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
	 * Indicate if post types settings has changed.
	 *
	 * @var bool
	 */
	public $is_post_types_changed = false;

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
	 * Retrieve the URL to the Doofinder settings page.
	 *
	 * @return string
	 */
	public static function get_url() {
		return menu_page_url( self::$top_level_menu, false );
	}

	/**
	 * Retrieve the API Key.
	 *
	 * Just an alias for "get_option" to avoid repeating the string
	 * (option name) in multiple files.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return get_option( 'doofinder_for_wp_api_key' );
	}

	/**
	 * Set the value of the API Key.
	 *
	 * Just an alias for "update_option" to avoid repeating the string
	 * (option name) in multiple files.
	 *
	 * @param string $api_key
	 */
	public static function set_api_key( $api_key ) {
		update_option( 'doofinder_for_wp_api_key', $api_key );
	}

	/**
	 * Retrieve the hash of the chosen Search engine.
	 *
	 * Just an alias for "get_option" to avoid repeating the string
	 * (option name) in multiple files.
	 *
	 * @param string $language Language code to retrieve the hash for.
	 *
	 * @return string
	 */
	public static function get_search_engine_hash( $language = '' ) {
		return get_option( self::option_name_for_language(
			'doofinder_for_wp_search_engine_hash',
			$language
		) );
	}

	/**
	 * Set the value of search engine hash.
	 *
	 * Just an alias for "update_option" to avoid repeating the string
	 * (option name) in multiple files.
	 *
	 * @param string $hash
	 * @param string $language Language code to set the hash for.
	 */
	public static function set_search_engine_hash( $hash, $language = '' ) {
		update_option( self::option_name_for_language(
			'doofinder_for_wp_search_engine_hash',
			$language
		), $hash );
	}

	/**
	 * Retrieve all the post types that the user chose to index.
	 *
	 * Just an alias for "get_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 *
	 * @return string[]
	 */
	public static function get_post_types_to_index( $language = '' ) {
		$post_types = get_option( self::option_name_for_language(
			'doofinder_for_wp_post_types_to_index',
			$language
		) );
		if ( ! $post_types ) {
			return array();
		}

		return array_keys( $post_types );
	}

	/**
	 * Set the value of post types to index.
	 *
	 * Just an alias for "update_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param array [string => 'on'] $post_types
	 * @param string $language Language code.
	 */
	public static function set_post_types_to_index( $post_types, $language = '' ) {
		update_option( self::option_name_for_language(
			'doofinder_for_wp_post_types_to_index',
			$language
		), $post_types );
	}

	/**
	 * Determine if the configuration is completed.
	 *
	 * Complete configuration means that API Key and Search Engine HashID fields are filled.
	 *
	 * @return bool
	 */
	public static function is_configuration_complete() {
		return (bool) ( self::get_api_key() && self::get_search_engine_hash() );
	}

	/**
	 * Determine if the JS Layer is enabled in the settings.
	 *
	 * Just an alias for "get_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 *
	 * @return bool
	 */
	public static function is_js_layer_enabled( $language = '' ) {
		return (bool) get_option( self::option_name_for_language(
			'doofinder_for_wp_enable_js_layer',
			$language
		) );
	}

	/**
	 * Enable JS Layer.
	 *
	 * Just an alias for "update_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 */
	public static function enable_js_layer( $language = '' ) {
		update_option( self::option_name_for_language(
			'doofinder_for_wp_enable_js_layer',
			$language
		), true );
	}

	/**
	 * Disable JS Layer.
	 *
	 * Just an alias for "update_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 */
	public static function disable_js_layer( $language = '' ) {
		update_option( self::option_name_for_language(
			'doofinder_for_wp_enable_js_layer',
			$language
		), false );
	}

	/**
	 * Retrieve the code of the JS Layer.
	 *
	 * Just an alias for "get_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 *
	 * @return string
	 */
	public static function get_js_layer( $language = '' ) {
		return wp_unslash( get_option( self::option_name_for_language(
			'doofinder_for_wp_js_layer',
			$language
		) ) );
	}

	/**
	 * Update the value of the JS Layer script.
	 *
	 * Just an alias for "update_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $value
	 * @param string $language Language code.
	 */
	public static function set_js_layer( $value, $language = '' ) {
		update_option( self::option_name_for_language( 'doofinder_for_wp_js_layer', $language ), $value );
	}

	/**
	 * Determine if the Internal Search is enabled.
	 *
	 * Just an alias for "get_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 *
	 * @return bool
	 */
	public static function is_internal_search_enabled( $language = '' ) {
		return (bool) get_option( self::option_name_for_language(
			'doofinder_for_wp_enable_internal_search',
			$language
		) );
	}

	/**
	 * Enable Internal Search.
	 *
	 * Just an alias for "update_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 */
	public static function enable_internal_search( $language = '' ) {
		update_option( self::option_name_for_language(
			'doofinder_for_wp_enable_internal_search',
			$language
		), true );
	}

	/**
	 * Disable Internal Search.
	 *
	 * Just an alias for "update_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 */
	public static function disable_internal_search( $language = '' ) {
		update_option( self::option_name_for_language(
			'doofinder_for_wp_enable_internal_search',
			$language
		), false );
	}

	/**
	 * Generate the name of the option for a given language.
	 *
	 * Values of the fields for different languages are stored under different options.
	 * Language code is added to option name, except for default language, because we want
	 * settings for default language be exactly the same as if language plugin
	 * was disabled.
	 *
	 * @param string $option_name Base option name, before adding a suffix.
	 * @param string $language Language code.
	 *
	 * @return string Option name with optionally added suffix.
	 */
	private static function option_name_for_language( $option_name, $language = '' ) {
		if ( $language ) {
			$option_name .= "_{$language}";
		} else {
			$language    = Multilanguage::instance();
			$option_name = $language->get_option_name( $option_name );
		}

		return $option_name;
	}

	/**
	 * Settings constructor.
	 */
	private function __construct() {
		$this->language = Multilanguage::instance();

		$this->add_api_key_settings();
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
	 * Create settings page.
	 *
	 * This function registers all settings fields and holds the names
	 * of all options.
	 *
	 * @since 1.0.0
	 */
	private function add_api_key_settings() {
		add_action( 'admin_init', function () {
			$indexing_data = Indexing_Data::instance();
			$is_indexing   = $indexing_data->get( 'status' ) === 'processing';

			/*
			 * Section 1 - Api Keys
			 */
			add_settings_section(
				'doofinder-for-wp-keys',
				__( 'Authentication', 'doofinder_for_wp' ),
				function () {
					?>
                    <p class="description"><?php _e( 'The following options allow to identify you and your search engine in Doofinder servers. Make sure you provide a Management API Key and not a Search API Key.', 'doofinder_for_wp' ); ?></p>
					<?php
				},
				self::$top_level_menu
			);

			// API Key
			$api_key_option_name = 'doofinder_for_wp_api_key';
			add_settings_field(
				$api_key_option_name,
				__( 'API Key', 'doofinder_for_wp' ),
				function () use ( $is_indexing, $api_key_option_name ) {
					if ( $is_indexing ) {
						$this->render_html_indexing_in_progress();

						return;
					}

					$this->render_html_api_key( $api_key_option_name );
				},
				self::$top_level_menu,
				'doofinder-for-wp-keys'
			);

			register_setting( self::$top_level_menu, $api_key_option_name, array( $this, 'validate_api_key' ) );

			// Search engine hash
			$search_engine_hash_option_name =
				$this->language->get_option_name( 'doofinder_for_wp_search_engine_hash' );
			add_settings_field(
				$search_engine_hash_option_name,
				__( 'Search Engine HashID', 'doofinder_for_wp' ),
				function () use ( $is_indexing, $search_engine_hash_option_name ) {
					if ( $is_indexing ) {
						$this->render_html_indexing_in_progress();

						return;
					}

					$this->render_html_search_engine_hash( $search_engine_hash_option_name );
				},
				self::$top_level_menu,
				'doofinder-for-wp-keys'
			);

			register_setting( self::$top_level_menu, $search_engine_hash_option_name, array(
				$this,
				'validate_search_engine_hash',
			) );

			/*
			 * Section 2 - Indexing settings
			 */
			add_settings_section(
				'doofinder-for-wp-indexing-settings',
				__( 'Index Settings', 'doofinder_for_wp' ),
				function () {
					?>

                    <p class="description"><?php _e( 'Configure the data you want to index in your search engine. You may need to reindex your content after changing some of these settings.', 'doofinder_for_wp' ); ?></p>

					<?php
				},
				self::$top_level_menu
			);

			// Post types to index
			$post_types_to_index_option_name =
				$this->language->get_option_name( 'doofinder_for_wp_post_types_to_index' );
			add_settings_field(
				$post_types_to_index_option_name,
				__( 'Post Types', 'doofinder_for_wp' ),
				function () use ( $is_indexing, $post_types_to_index_option_name ) {
					if ( $is_indexing ) {
						$this->render_html_indexing_in_progress();

						return;
					}

					$this->render_html_post_types_to_index( $post_types_to_index_option_name );
				},
				self::$top_level_menu,
				'doofinder-for-wp-indexing-settings'
			);

			register_setting( self::$top_level_menu, $post_types_to_index_option_name, array(
				$this,
				'validate_post_types',
			) );

			/*
			 * Section 3 - Search settings
			 */
			add_settings_section(
				'doofinder-for-wp-search-settings',
				__( 'Search Settings', 'doofinder_for_wp' ),
				function () {
					?>
                    <p class="description"><?php _e( 'Configure how Doofinder will integrate with your site.', 'doofinder_for_wp' ); ?></p>
					<?php
				},
				self::$top_level_menu
			);

			// Enable JS Layer
			$enable_js_layer_option_name =
				$this->language->get_option_name( 'doofinder_for_wp_enable_js_layer' );
			add_settings_field(
				$enable_js_layer_option_name,
				__( 'JS Layer', 'doofinder_for_wp' ),
				function () use ( $enable_js_layer_option_name ) {
					$this->render_html_enable_js_layer( $enable_js_layer_option_name );
				},
				self::$top_level_menu,
				'doofinder-for-wp-search-settings'
			);

			register_setting( self::$top_level_menu, $enable_js_layer_option_name );

			// JS Layer
			$js_layer_option_name =
				$this->language->get_option_name( 'doofinder_for_wp_js_layer' );
			add_settings_field(
				$js_layer_option_name,
				__( 'JS Layer Script', 'doofinder_for_wp' ),
				function () use ( $js_layer_option_name ) {
					$this->render_html_js_layer( $js_layer_option_name );
				},
				self::$top_level_menu,
				'doofinder-for-wp-search-settings'
			);

			register_setting( self::$top_level_menu, $js_layer_option_name );

			// Enable Internal Search
			$internal_search_option_name =
				$this->language->get_option_name( 'doofinder_for_wp_enable_internal_search' );
			add_settings_field(
				$internal_search_option_name,
				__( 'Internal Search', 'doofinder_for_wp' ),
				function () use ( $internal_search_option_name ) {
					$this->render_html_enable_internal_search( $internal_search_option_name );
				},
				self::$top_level_menu,
				'doofinder-for-wp-search-settings'
			);

			register_setting( self::$top_level_menu, $internal_search_option_name );
		} );
	}

	/**
	 * Add top level menu.
	 *
	 * @since 1.0.0
	 */
	private function add_settings_page() {
		add_action( 'admin_menu', function () {
			add_menu_page(
				'Doofinder For WordPress',
				'Doofinder',
				'manage_options',
				self::$top_level_menu,
				function () {
					$this->render_html_settings_page();
				},
				'dashicons-search'
			);
		} );
	}

	/**
	 * Display form for doofinder settings page.
	 *
	 * If language plugin is active, but no language is selected we'll prompt the user
	 * to select a language instead of displaying settings.
	 *
	 * @since 1.0.0
	 */
	private function render_html_settings_page() {
		if ( ( $this->language instanceof No_Language_Plugin ) || $this->language->get_active_language() ) {
			$this->render_html_settings();

			return;
		}

		$this->render_html_pick_language_prompt();
	}

	/**
	 * Display the settings.
	 */
	private function render_html_settings() {
		// only users that have access to wp settings can view this form
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add update messages if doesn't exist
		$errors = get_settings_errors( 'doofinder_for_wp_messages' );

		if ( isset( $_GET['settings-updated'] ) && ! $this->in_2d_array( 'doofinder_for_wp_message', $errors ) ) {
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message', __( 'Settings Saved', 'doofinder_for_wp' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'doofinder_for_wp_messages' );
		get_settings_errors( 'doofinder_for_wp_messages' );

		?>

        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <form action="options.php" method="post">
				<?php

				settings_fields( self::$top_level_menu );
				do_settings_sections( self::$top_level_menu );
				submit_button( 'Save Settings' );

				?>
            </form>
        </div>

		<?php
	}

	/**
	 * Prompt the user to select a language.
	 */
	private function render_html_pick_language_prompt() {
		?>

        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="notice notice-error">
                <p><?php _e( 'You have a multi-language plugin installed. Please choose language first to configure Doofinder.', 'doofinder_for_wp' ); ?></p>
            </div>
        </div>

		<?php
	}

	/**
	 * Print HTML for the "API Key" option.
	 *
	 * @param string $option_name
	 */
	private function render_html_api_key( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

        <span class="doofinder-tooltip"><span><?php _e( 'The secret token used to authenticate requests.', 'doofinder_for_wp' ); ?></span></span>
        <input type="text"
               name="<?php echo $option_name; ?>"
               class="widefat"

			<?php if ( $saved_value ): ?>
                value="<?php echo $saved_value; ?>"
			<?php endif; ?>
        >

		<?php
	}

	/**
	 * Print HTML for the "Search engine hash" option.
	 *
	 * @param string $option_name
	 */
	private function render_html_search_engine_hash( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

        <span class="doofinder-tooltip"><span><?php _e( 'The id of a search engine in your Doofinder Account.', 'doofinder_for_wp' ); ?></span></span>

        <input type="text"
               name="<?php echo $option_name; ?>"
               class="widefat"

			<?php if ( $saved_value ): ?>
                value="<?php echo $saved_value; ?>"
			<?php endif; ?>
        >

		<?php
	}

	/**
	 * Print HTML with checkboxes where user can select
	 * which post types to index.
	 *
	 * @param string $option_name
	 */
	private function render_html_post_types_to_index( $option_name ) {
		// Saved list of post types.
		$saved_value = get_option( $option_name );

		// We later check in array, if option is empty,
		// then empty string is returned.
		if ( ! $saved_value ) {
			$saved_value = array();
		}

		?>
        <span class="doofinder-tooltip"><span><?php _e( 'You must reindex your content after changing this setting.', 'doofinder_for_wp' ); ?></span></span>
		<?php

		// Output checkboxes with post types.
		$post_types = Post_Types::instance();
		foreach ( $post_types->get() as $post_type ) {
			$checked = array_key_exists( $post_type, $saved_value );
			if ( ! $saved_value && Post_Types::is_default( $post_type ) ) {
				$checked = true;
			}

			?>

            <label>
                <input type="checkbox"
                       name="<?php echo $option_name; ?>[<?php echo $post_type; ?>]"

					<?php if ( $checked ): ?>
                        checked
					<?php endif; ?>
                >

				<?php

				// Get full name of the post type.
				$post_type_object = get_post_type_object( $post_type );
				echo $post_type_object->labels->name;

				?>&nbsp;&nbsp;
            </label>

			<?php
		}
	}

	/**
	 * Render a checkbox allowing user to enable / disable the JS layer.
	 *
	 * @param string $option_name
	 */
	private function render_html_enable_js_layer( $option_name ) {
		$saved_value = get_option( $option_name );

		?>
        <span class="doofinder-tooltip"><span><?php _e( 'The JS Layer script will be added to your site\'s template.', 'doofinder_for_wp' ); ?></span></span>
        <label>
            <input type="checkbox" name="<?php echo $option_name; ?>"
				<?php if ( $saved_value ): ?>
                    checked
				<?php endif; ?>
            >

			<?php _e( 'Enable JS Layer', 'doofinder_for_wp' ); ?>
        </label>

		<?php
	}

	/**
	 * Render the textarea containing Doofinder JS Layer code.
	 *
	 * @param string $option_name
	 */
	private function render_html_js_layer( $option_name ) {
		$saved_value = get_option( $option_name );

		?>
        <span class="doofinder-tooltip"><span><?php _e( 'Paste here the JS Layer code obtained from Doofinder.', 'doofinder_for_wp' ); ?></span></span>
        <textarea name="<?php echo $option_name; ?>" class="widefat" rows="16"><?php

			if ( $saved_value ) {
				echo wp_unslash( $saved_value );
			}

			?></textarea>

		<?php
	}

	/**
	 * Render checkbox allowing users to enable/disable the Internal Search.
	 *
	 * @param string $option_name
	 */
	private function render_html_enable_internal_search( $option_name ) {
		$saved_value = get_option( $option_name );

		?>

        <span class="doofinder-tooltip"><span><?php _e( 'Enabling this setting will make WordPress use Doofinder internally for search.', 'doofinder_for_wp' ); ?></span></span>
        <label>
            <input type="checkbox" name="<?php echo $option_name; ?>"
				<?php if ( $saved_value ): ?>
                    checked
				<?php endif; ?>
            >

			<?php _e( 'Enable Internal Search', 'doofinder_for_wp' ); ?>
        </label>

		<?php
	}

	/**
	 * Print the information that indexing is in progress.
	 *
	 * We cannot change some options (e.g. which post types to index)
	 * if we are already indexing.
	 */
	private function render_html_indexing_in_progress() {
		?>

        <i><?php _e( 'Indexing is in progress. Wait until indexing finishes before changing the settings.', 'doofinder_for_wp' ); ?></i>

		<?php
	}

	/**
	 * Validate api key.
	 *
	 * @param string
	 *
	 * @return string
	 */
	function validate_api_key( $input ) {
		if ( null == $input ) {
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message_api_key', __( 'API Key is mandatory.', 'doofinder_for_wp' ) );
		}

		return $input;
	}

	/**
	 * Validate search engine hash.
	 *
	 * @param string $input
	 *
	 * @return string $input
	 */
	public function validate_search_engine_hash( $input ) {
		if ( null == $input ) {
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message_search_engine_hash', __( 'HashID is mandatory.', 'doofinder_for_wp' ) );
		}

		return $input;
	}

	/**
	 * Validate post types.
	 *
	 * If post types differ then display info for reindex posts.
	 *
	 * @param array $input
	 *
	 * @return array $input
	 */
	public function validate_post_types( $input ) {
		$default_post_types = array( 'page', 'post' );
		$old_post_types     = $this->get_post_types_to_index();

		if ( ! $old_post_types ) {
			$old_post_types = $default_post_types;
		}

		if ( ! $input ) {
			$post_types = $default_post_types;
		} else {
			$post_types = array_keys( $input );
		}

		sort( $old_post_types );
		sort( $post_types );

		if ( $post_types != $old_post_types ) {
			$url = admin_url( 'admin.php?page=index_posts' );
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message', sprintf( __( 'Settings Saved: please, <a href="%s">reindex content</a> for changes to take effect.', 'doofinder_for_wp' ), $url ), 'updated' );
		}

		return $input;
	}

	/**
	 * Helper function to search two dimensional array.
	 *
	 * @param string $needle
	 * @param array  $haystack
	 *
	 * @return bool
	 */
	private function in_2d_array( $needle, array $haystack ) {
		foreach ( $haystack as $array ) {
			if ( in_array( $needle, $array ) ) {
				return true;
			}
		}

		return false;
	}
}
