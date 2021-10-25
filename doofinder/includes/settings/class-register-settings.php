<?php

namespace Doofinder\WP\Settings;

use Doofinder\WP\Indexing_Data;
use Doofinder\WP\Multilanguage\Language_Plugin;

defined( 'ABSPATH' ) or die();

/**
 * Contains all settings registration / all calls to Settings API.
 *
 * @property Language_Plugin $language
 */
trait Register_Settings {

	/**
	 * Create settings page.
	 *
	 * This function registers all settings fields and holds the names
	 * of all options.
	 *
	 * @since 1.0.0
	 */
	private function add_plugin_settings() {
		add_action( 'admin_init', function () {
			// When saving settings make sure not to register settings if we are not
			// saving our own settings page. If the current action is called on
			// the settings page of another plugin it might cause conflicts.
			if (
				// If we are saving the settings...
				$_SERVER['REQUEST_METHOD'] === 'POST'
				&& (
					// ...and "option_page" is either not present...
					! isset( $_POST['option_page'] )

					// ...or is set to something else than our custom page.
					|| $_POST['option_page'] !== self::$top_level_menu
				)
			) {
				return;
			}

			// Figure out which tab is open / which tab is being saved.
			if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
				$selected_tab = $_POST['doofinder_for_wp_selected_tab'];
			} elseif ( isset( $_GET['tab'] ) ) {
				$selected_tab = $_GET['tab'];
			} else {
				$selected_tab = array_keys( self::$tabs )[0];
			}

			if ( ! isset( self::$tabs[ $selected_tab ] ) ) {
				return;
			}

			call_user_func( [ $this, self::$tabs[ $selected_tab ]['fields_cb'] ] );
		} );
	}

	/**
	 * Section 1 / tab 1 fields.
	 *
	 * IDE might report this as unused, because it's dynamically called.
	 *
	 * @see Settings::$tabs
	 */
	private function add_authentication_settings() {
		$indexing_data = Indexing_Data::instance();
		$is_indexing   = $indexing_data->get( 'status' ) === 'processing';

		add_settings_section(
			'doofinder-for-wp-keys',
			__( 'Authentication', 'doofinder_for_wp' ),
			function () {
				?>
                <p class="description"><?php _e( 'The following options allow to identify you and your search engine in Doofinder servers. Make sure you provide a Management API Key and not a Search API Key.',
						'doofinder_for_wp' ); ?></p>
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

        // API Host
		$api_host_option_name = 'doofinder_for_wp_api_host';
		add_settings_field(
			$api_host_option_name,
			__( 'API Host', 'doofinder_for_wp' ),
			function () use ( $is_indexing, $api_host_option_name ) {
				if ( $is_indexing ) {
					$this->render_html_indexing_in_progress();

					return;
				}

				$this->render_html_api_key( $api_host_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-keys'
		);

		register_setting( self::$top_level_menu, $api_host_option_name, array( $this, 'validate_api_host' ) );

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

		add_settings_section(
			'doofinder-for-wp-debug-mode',
			__( 'Debug mode', 'doofinder_for_wp' ),
			function () { },
			self::$top_level_menu
		);

		// Disable debug mode
		$disable_debug_mode_option_name =
			$this->language->get_option_name( 'doofinder_for_wp_disable_debug_mode' );
		add_settings_field(
			$disable_debug_mode_option_name,
			__( 'Disable debug mode', 'doofinder_for_wp' ),
			function () use ( $is_indexing, $disable_debug_mode_option_name ) {
				if ( $is_indexing ) {
					$this->render_html_indexing_in_progress();

					return;
				}

				echo $this->render_html_disable_debug_mode( $disable_debug_mode_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-debug-mode'
		);

		register_setting(
			self::$top_level_menu,
			$disable_debug_mode_option_name,
			array( $this, 'validate_api_key' )
		);
	}

	/**
	 * Section 2 / tab 2 fields.
	 *
	 * IDE might report this as unused, because it's dynamically called.
	 *
	 * @see Settings::$tabs
	 */
	private function add_data_settings() {
		$indexing_data = Indexing_Data::instance();
		$is_indexing   = $indexing_data->get( 'status' ) === 'processing';

		add_settings_section(
			'doofinder-for-wp-indexing-settings',
			__( 'Index Settings', 'doofinder_for_wp' ),
			function () {
				?>

                <p class="description"><?php _e( 'Configure the data you want to index in your search engine. You may need to reindex your content after changing some of these settings.',
						'doofinder_for_wp' ); ?></p>

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

		// Index categories
		$index_categories_option_name =
			$this->language->get_option_name( 'doofinder_for_wp_index_categories' );
		add_settings_field(
			$index_categories_option_name,
			__( 'Index Categories', 'doofinder_for_wp' ),
			function () use ( $is_indexing, $index_categories_option_name ) {
				if ( $is_indexing ) {
					$this->render_html_indexing_in_progress();

					return;
				}

				$this->render_html_index_categories( $index_categories_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-indexing-settings'
		);

		register_setting( self::$top_level_menu, $index_categories_option_name );

		// Index tags
		$index_tags_option_name =
			$this->language->get_option_name( 'doofinder_for_wp_index_tags' );
		add_settings_field(
			$index_tags_option_name,
			__( 'Index Tags', 'doofinder_for_wp' ),
			function () use ( $is_indexing, $index_tags_option_name ) {
				if ( $is_indexing ) {
					$this->render_html_indexing_in_progress();

					return;
				}

				$this->render_html_index_tags( $index_tags_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-indexing-settings'
		);

		register_setting( self::$top_level_menu, $index_tags_option_name );

		// Additional attributes
		$additional_attributes_option_name =
			$this->language->get_option_name( 'doofinder_for_wp_additional_attributes' );
		add_settings_field(
			$additional_attributes_option_name,
			__( 'Additional Attributes', 'doofinder_for_wp' ),
			function () use ( $additional_attributes_option_name ) {
				$this->render_html_additional_attributes( $additional_attributes_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-indexing-settings'
		);

		register_setting(
			self::$top_level_menu,
			$additional_attributes_option_name,
			array( $this, 'sanitize_additional_attributes' )
		);
	}

	/**
	 * Section 3 / tab 3 fields.
	 *
	 * IDE might report this as unused, because it's dynamically called.
	 *
	 * @see Settings::$tabs
	 */
	private function add_search_settings() {
		add_settings_section(
			'doofinder-for-wp-search-settings',
			__( 'Search Settings', 'doofinder_for_wp' ),
			function () {
				?>
                <p class="description"><?php _e( 'Configure how Doofinder will integrate with your site.',
						'doofinder_for_wp' ); ?></p>
				<?php
			},
			self::$top_level_menu
		);

		// Enable Internal Search
		$internal_search_option_name =
			$this->language->get_option_name( 'doofinder_for_wp_enable_internal_search' );
		add_settings_field(
			$internal_search_option_name,
			__( 'Enable Internal Search', 'doofinder_for_wp' ),
			function () use ( $internal_search_option_name ) {
				$this->render_html_enable_internal_search( $internal_search_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-search-settings'
		);

		register_setting( self::$top_level_menu, $internal_search_option_name );

		// Enable JS Layer
		$enable_js_layer_option_name =
			$this->language->get_option_name( 'doofinder_for_wp_enable_js_layer' );
		add_settings_field(
			$enable_js_layer_option_name,
			__( 'Enable JS Layer', 'doofinder_for_wp' ),
			function () use ( $enable_js_layer_option_name ) {
				$this->render_html_enable_js_layer( $enable_js_layer_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-search-settings'
		);

		register_setting( self::$top_level_menu, $enable_js_layer_option_name );

		// Load layer directly from Doofinder
		$load_js_layer_from_doofinder_option_name =
			$this->language->get_option_name( 'doofinder_for_wp_load_js_layer_from_doofinder' );
		add_settings_field(
			$load_js_layer_from_doofinder_option_name,
			__( 'Load JS Layer directly from Doofinder', 'doofinder_for_wp' ),
			function () use ( $load_js_layer_from_doofinder_option_name ) {
				$this->render_html_load_js_layer_from_doofinder( $load_js_layer_from_doofinder_option_name );
			},
			self::$top_level_menu,
			'doofinder-for-wp-search-settings'
		);

		register_setting( self::$top_level_menu, $load_js_layer_from_doofinder_option_name );

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
	 * Validate api key.
	 *
	 * @param string
	 *
	 * @return string
	 */
	function validate_api_key( $input ) {
		if ( null == $input ) {
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message_api_key',
				__( 'API Key is mandatory.', 'doofinder_for_wp' ) );
		}

		return $input;
	}

    /**
	 * Validate api host.
	 *
	 * @param string
	 *
	 * @return string
	 */
	function validate_api_host( $input ) {
		if ( null == $input ) {
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message_api_host',
				__( 'API Host is mandatory.', 'doofinder_for_wp' ) );
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
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message_search_engine_hash',
				__( 'HashID is mandatory.', 'doofinder_for_wp' ) );
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
			add_settings_error( 'doofinder_for_wp_messages', 'doofinder_for_wp_message',
				sprintf( __( 'Settings Saved: please, <a href="%s">reindex content</a> for changes to take effect.',
					'doofinder_for_wp' ), $url ), 'updated' );
		}

		return $input;
	}

	/**
	 * Process additional attributes sent from the frontend
	 * and convert them to the shape we want to store in the DB.
	 *
	 * This functional basically converts indexes, so we save a nice
	 * regular numerically-indexed array, and removes all records
	 * that are either selected to be deleted, or invalid.
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public function sanitize_additional_attributes( $input ) {
		$output = array();

		// We want to save a regular array containing all attributes,
		// but what we send from the frontend is an associative array
		// (because it has "new" entry).
		// Convert data from frontend to nicely-indexed regular array,
		// removing all the records that we want to delete, and those
		// with empty "field" value along the way.
		foreach ( $input as $attribute ) {
			if ( ! $attribute['field'] ) {
				continue;
			}

			if ( isset( $attribute['delete'] ) && $attribute['delete'] ) {
				continue;
			}

			$output[] = $attribute;
		}

		return $output;
	}
}
