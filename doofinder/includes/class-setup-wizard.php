<?php

namespace Doofinder\WP;

use Doofinder\WP\Multilanguage\Language_Plugin;
use Doofinder\WP\Multilanguage\Multilanguage;

class Setup_Wizard {

	/**
	 * Singleton instance of this class.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Name of the option determining whether or not setup wizard
	 * was already performed.
	 *
	 * @var string
	 */
	private static $wizard_done_option = 'doofinder_for_wp_setup_wizard_done';

	/**
	 * Name of the option determining whether or not setup wizard
	 * should be displayed to the user.
	 *
	 * @var string
	 */
	private static $wizard_active_option = 'doofinder_for_wp_setup_wizard_active';

	/**
	 * Name of the option storing the current step of the wizard.
	 *
	 * @var string
	 */
	private static $wizard_step_option = 'doofinder_for_wp_setup_wizard_step';

	/**
	 * How many steps does the wizard have.
	 *
	 * @var int
	 */
	private static $no_steps = 3;

	/**
	 * Instance of the class handling the multilanguage.
	 *
	 * @var Language_Plugin
	 */
	private $language;

	/**
	 * Errors to display for the form fields.
	 *
	 * Index is the form field name, value is the error text.
	 *
	 * @var array[string => string]
	 */
	private $errors = array();

	/**
	 * Check if we should enable the setup wizard, or if it's
	 * not necessary (because for example, it's been already performed).
	 *
	 * @return bool
	 */
	public static function should_activate() {
		$after_wizard = get_option( self::$wizard_done_option );

		return ! (bool) $after_wizard;
	}

	/**
	 * Activate the setup wizard.
	 *
	 * When it is active (the option is set to truthy value) users that can
	 * manage options will see custom screen (the setup wizard) instead
	 * of admin panel.
	 */
	public static function activate() {
		update_option( self::$wizard_active_option, true );
	}

	/**
	 * Deactivate the setup wizard and set the flag making sure
	 * to not display it anymore.
	 */
	public static function deactivate() {
		update_option( self::$wizard_active_option, false );
		update_option( self::$wizard_done_option, true );
	}

	/**
	 * Is the setup wizard active (should we display it)?
	 *
	 * @return bool
	 */
	public static function is_active() {
		return (bool) get_option( self::$wizard_active_option );
	}

	/**
	 * What the current step of the wizard is? This is the last step
	 * that the user have seen and not submitted yet.
	 *
	 * @return int
	 */
	public static function get_step() {
		$step = get_option( self::$wizard_step_option );
		if ( ! $step ) {
			$step = 1;
		}

		return (int) $step;
	}

	/**
	 * Move to the next step. If this was the last step
	 * deactivate the Setup Wizard.
	 */
	public static function next_step() {
		$current_step = self::get_step();
		$current_step ++;

		if ( $current_step > self::$no_steps ) {
			self::deactivate();

			wp_safe_redirect( Settings::get_url() );
			die();
		}

		update_option( self::$wizard_step_option, $current_step );
	}

	/**
	 * Create (or retrieve, if already exists), the singleton
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

	public function __construct() {
		$this->language = Multilanguage::instance();

		// Show wizard, if active.
		add_action( 'admin_init', function () {
			if ( self::is_active() ) {
				// Handle POST submissions, if present.
				if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					$this->handle_step_submit();
				}

				$this->show_wizard();
			}
		} );
	}

	/**
	 * Display the setup wizard view.
	 */
	private function show_wizard() {
		include Doofinder_For_WordPress::plugin_path() . '/views/wizard.php';

		// We only want to show our screen, don't give control
		// back to WordPress.
		exit();
	}

	/**
	 * Render the HTML of the current wizard step.
	 *
	 * This is used in the view, so might be displayed
	 * as not used in the IDE.
	 */
	private function render_wizard_step() {
		$step = self::get_step();
		if ( $step > self::$no_steps ) {
			$step = self::$no_steps;
		}

		include Doofinder_For_WordPress::plugin_path() . "/views/wizard-step-$step.php";
	}

	/**
	 * Get the error for a given field.
	 *
	 * This function is used in the views, so might be reported
	 * as not used in the IDE.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	private function get_error( $name ) {
		if ( isset( $this->errors[ $name ] ) ) {
			return $this->errors[ $name ];
		}

		return null;
	}

	/**
	 * A callback for form POST in installation wizard.
	 *
	 * Each step of the form is being handled by its own method.
	 */
	private function handle_step_submit() {
		switch ( self::get_step() ) {
			case 1:
				$this->handle_step_1_submit();
				break;

			case 2:
				$this->handle_step_2_submit();
				break;

			case 3:
				$this->handle_step_3_submit();
				break;
		}
	}

	/**
	 * Handle the submission of step 1 - Api Key and Search Engine Hash.
	 */
	private function handle_step_1_submit() {
		// Required fields are not present.
		if (
			! isset( $_POST['api-key'] ) || ! $_POST['api-key'] ||
			! isset( $_POST['search-engine-hash'] ) || ! $_POST['search-engine-hash']
		) {
			if ( ! isset( $_POST['api-key'] ) || ! $_POST['api-key'] ) {
				$this->errors['api-key'] = __( 'This field is required', 'doofinder_for_api' );
			}

			if ( ! isset( $_POST['search-engine-hash'] ) || ! $_POST['search-engine-hash'] ) {
				$this->errors['search-engine-hash'] = __( 'This field is required', 'doofinder_for_api' );
			}

			return;
		}

		// Everything is ok - save the options
		Settings::set_api_key( $_POST['api-key'] );
		Settings::set_search_engine_hash( $_POST['search-engine-hash'] );

		// Save the options for each language, if provided.
		if ( $this->language->get_languages() ) {
			foreach ( $this->language->get_languages() as $language_code => $language_name ) {
				// The case for base language is handled above.
				if ( $language_code === $this->language->get_base_language() ) {
					continue;
				}

				$field_name = "search-engine-hash-{$language_code}";
				if ( isset( $_POST[ $field_name ] ) && $_POST[ $field_name ] ) {
					Settings::set_search_engine_hash( $_POST[ $field_name ], $language_code );
				}
			}
		}

		// ...and move to the next step.
		self::next_step();
	}

	/**
	 * Handle the submission of step 2 - Post types to index.
	 */
	private function handle_step_2_submit() {
		// We require that the user selects at least one post type.
		// If nothing is selected, the plugin will fallback to default ["post", "page"],
		// but during the wizard let's force the user to select at least one
		// to make the choice explicit.
		if ( ! isset( $_POST['post-types-to-index'] ) || ! $_POST['post-types-to-index'] ) {
			$this->errors['post-types-to-index'] = __( 'Select at least one post type', 'doofinder_for_wp' );

			return;
		}

		// Everything ok. Save the option and move to the next step.
		Settings::set_post_types_to_index( $_POST['post-types-to-index'] );

		// Save all other languages
		if ( $this->language->get_languages() ) {
			foreach ( $this->language->get_languages() as $language_code => $language_name ) {
				if (
					! isset( $_POST["post-types-to-index-{$language_code}"] ) ||
					! $_POST["post-types-to-index-{$language_code}"]
				) {
					continue;
				}

				Settings::set_post_types_to_index( $_POST["post-types-to-index-{$language_code}"], $language_code );
			}
		}

		// Move to the next step.
		self::next_step();
	}

	/**
	 * Handle the submit of step 3 - JS Layer / Internal search.
	 */
	private function handle_step_3_submit() {
		// If there's no plugin active we still need to process 1 language.
		$languages = $this->language->get_languages();
		if ( ! $languages ) {
			$languages[''] = '';
		}

		foreach ( $languages as $language_code => $language_name ) {
			// Suffix for options.
			// This should be empty for default language, and language code
			// for any other.
			$options_suffix = '';
			$name_suffix    = '';
			if ( $language_code !== $this->language->get_base_language() ) {
				$options_suffix = $language_code;
				$name_suffix    = "-$language_code";
			}

			// JS Layer
			if ( isset( $_POST["enable-js-layer{$name_suffix}"] ) && $_POST["enable-js-layer{$name_suffix}"] ) {
				Settings::enable_js_layer( $options_suffix );
			} else {
				Settings::disable_js_layer( $options_suffix );
			}

			// JS Layer Code
			if ( isset( $_POST["js-layer-code{$name_suffix}"] ) && $_POST["js-layer-code{$name_suffix}"] ) {
				Settings::set_js_layer( $_POST['js-layer-code'], $options_suffix );
			}

			// Internal Search
			if (
				isset( $_POST["enable-internal-search{$name_suffix}"] ) &&
				$_POST["enable-internal-search{$name_suffix}"]
			) {
				Settings::enable_internal_search( $options_suffix );
			} else {
				Settings::disable_internal_search( $options_suffix );
			}
		}

		self::next_step();
	}
}
