<?php

namespace Doofinder\WP;

use Doofinder\WP\Multilanguage\Language_Plugin;
use Doofinder\WP\Multilanguage\Multilanguage;
use Doofinder\WP\Multilanguage\No_Language_Plugin;

/**
 * Prints interface that allows user to index the posts.
 */
class Index_Interface {

	/**
	 * The only instance of Index_Interface
	 *
	 * @var Index_Interface
	 */
	private static $_instance = null;

	/**
	 * Instance of the class handling multilanguage.
	 *
	 * @var Language_Plugin
	 */
	private $language;

	/**
	 * Contains information about indexing progress.
	 *
	 * @var Indexing_Data
	 */
	private $indexing_data;

	/**
	 * Returns the only instance of Index_Interface
	 *
	 * @since 1.0.0
	 * @return Index_Interface
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Index_Interface constructor.
	 */
	private function __construct() {
		$this->language      = Multilanguage::instance();
		$this->indexing_data = Indexing_Data::instance();

		// Add a submenu page with indexing interface.
		$this->add_indexing_subpage();

		// Register JS action that will handle sending one batch of data to API.
		$this->register_ajax_action();

		// Add frontend scripts.
		$this->add_admin_scripts();
	}

	/**
	 * Add a subpage displaying the interface allowing to index
	 * all posts from the blog.
	 */
	private function add_indexing_subpage() {
		add_action( 'admin_menu', function () {
			add_submenu_page(
				Settings::$top_level_menu,
				__( 'Index Posts', 'doofinder_for_wp' ),
				__( 'Index Posts', 'doofinder_for_wp' ),
				'manage_options',
				'index_posts',
				function () {
					$this->render_html_subpage();
				}
			);
		} );
	}

	/**
	 * Register an ajax action that indexes (sends to the Doofinder API) a single batch
	 * of the posts.
	 *
	 * JS will call this endpoint multiple time, each time adding new batch of posts.
	 *
	 * @since 1.0.0
	 */
	private function register_ajax_action() {
		add_action( 'wp_ajax_doofinder_for_wp_index_content', function () {
			$data = new Data_Index();
			$data->ajax_handler();
		} );
	}

	/**
	 * Register scripts used by the indexing interface.
	 */
	private function add_admin_scripts() {
		add_action( 'admin_enqueue_scripts', function () {
			// Don't add these scripts on pages other than the indexing interface.
			// Other pages don't use them.
			$screen = get_current_screen();
			if ( $screen->id !== 'doofinder_page_index_posts' ) {
				return;
			}

			// JS
			wp_enqueue_script( 'doofinder-for-wp-script',
				Doofinder_For_WordPress::plugin_url() . '/assets/js/admin.js',
				array( 'jquery' )
			);
			wp_localize_script( 'doofinder-for-wp-script', 'DoofinderForWP', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			) );

			// CSS
			wp_enqueue_style(
				'doofinder-for-wp-styles',
				Doofinder_For_WordPress::plugin_url() . '/assets/css/admin.css'
			);
		} );
	}

	/**
	 * Check if language is selected (provided a multilanguage plugin is active).
	 *
	 * If no multilanguage plugins are active this function will return true,
	 * because in that case it's not possible to deselect language.
	 *
	 * @return bool
	 */
	private function is_language_selected() {
		return ( $this->language instanceof No_Language_Plugin ) || $this->language->get_active_language();
	}

	/**
	 * Check if API key and search engine hash are set in settings
	 * for the current language. Indexing will be impossible if
	 * they are missing.
	 *
	 * @return bool
	 */
	private function are_api_keys_present() {
		$api_key = Settings::get_api_key();
		$hash    = Settings::get_search_engine_hash();

		return ( $api_key && $hash );
	}

	/**
	 * Generate the HTML of the indexing page.
	 */
	private function render_html_subpage() {
		?>

        <div class="wrap">
            <h1><?php _e( 'Index Posts', 'doofinder_for_wp' ); ?></h1>

			<?php

			// If multilanguage is active, but there is no active language selected.
			if ( $this->is_language_selected() && $this->are_api_keys_present() ) {
				$this->render_html_wp_debug_warning();
				$this->render_html_processing_status();
				$this->render_html_progress_bar();
				$this->render_html_progress_bar_status();
				$this->render_html_indexing_messages();
				$this->render_html_indexing_error();
				$this->render_html_index_button();
			} else {
				$this->render_html_settings_error();
			}

			?>
        </div>

		<?php
	}

	/**
	 * Ask user to select a language.
	 */
	private function render_html_settings_error() {
		if ( ! $this->is_language_selected() ) {
			$this->render_html_select_language();

			return;
		}

		$this->render_html_missing_api_keys();
	}

	/**
	 * Render error notifying the user that they should select
	 * a language first.
	 */
	private function render_html_select_language() {
		?>

        <div class="notice notice-error">
            <p><?php _e( 'You have a multi-language plugin installed. Please choose a language first to index data in Doofinder.', 'doofinder_for_wp' ); ?></p>
        </div>

		<?php
	}

	/**
	 * Render error notifying the user that API keys are missing.
	 */
	private function render_html_missing_api_keys() {
		?>

        <div class="notice notice-error"><p><?php _e( 'API Key and/or Search Engine Hash ID are not set in Doofinder Settings for the selected language.', 'doofinder_for_wp' ); ?></p></div>

		<?php
	}

	/**
	 * Because if WP_DEBUG is turned on we'll logging to local file instead of sending
	 * to the API, let's display the warning so the user knows what's going on.
	 */
	private function render_html_wp_debug_warning() {
		if ( ! WP_DEBUG ) {
			return;
		}

		?>

        <p class="doofinder-for-wp-warning"><?php _e( 'Your site is in debug mode. Nothing will be sent to the Doofinder API.', 'doofinder_for_wp' ); ?></p>

		<?php
	}

	/**
	 * Display additional information about the status/progress
	 * of the indexing process.
	 */
	private function render_html_processing_status() {
		$status = $this->indexing_data->get( 'status' );

		if ( ! Settings::is_configuration_complete() ) {
			$url = admin_url( 'admin.php?page=doofinder_for_wp' );
			?>

            <div class="error settings-error notice">
                <p><?php printf( __( 'Indexing posts is unavailable. Check your <a href="%s">configuration</a> and try again.', 'doofinder_for_wp' ), $url ); ?></p>
            </div>

			<?php
		} elseif ( $status === 'new' ) {
			?>

            <div class="error settings-error notice">
                <p><?php _e( 'Your data must be reindexed. No data found in Doofinder or it\'s outdated.', 'doofinder_for_wp' ); ?></p>
            </div>

			<?php
		} elseif ( $status === 'processing' ) {
			?>

            <div class="error settings-error notice">
                <p><?php _e( 'Indexing posts is in progress, but was interrupted.', 'doofinder_for_wp' ); ?></p>
            </div>

			<?php
		} elseif ( $status === 'completed' && isset( $_COOKIE['doofinder_wp_show_success_message'] ) ) {
			?>

            <div class="updated settings-error notice">
                <p><?php _e( 'Indices are ready and up-to-date.', 'doofinder_for_wp' ); ?></p>
            </div>

			<?php

			// Clear $_COOKIE value and browser cookie file
			unset( $_COOKIE['doofinder_wp_show_success_message'] );
			setCookie( 'doofinder_wp_show_success_message', null, - 1 );
		}
	}

	/**
	 * Render progress bar displaying the progress of indexing process.
	 *
	 * JS will update it as indexing goes on.
	 */
	private function render_html_progress_bar() {
		?>

        <div id="doofinder-for-wp-progress-bar" class="doofinder-for-wp-progress-bar">
            <div class="doofinder-for-wp-bar" data-bar></div>
        </div>

		<?php
	}

	/**
	 * Render message displaying the status of indexing process.
	 *
	 * JS will update it as indexing goes on.
	 */
	private function render_html_progress_bar_status() {
		?>

        <div id="doofinder-for-wp-progress-bar-status" class="doofinder-for-wp-progress-bar-status">
            <p class="preparing"><?php _e( 'Preparing to index: be patient, it can take some time. Don\'t leave this page.', 'doofinder_for_wp' ); ?></p>
            <p class="indexing"><?php _e( 'Indexing content. Don\'t leave this page.', 'doofinder_for_wp' ); ?></p>
        </div>

		<?php
	}

	/**
	 * Render additional messages providing additional information
	 * about what the backend is doing.
	 */
	private function render_html_indexing_messages() {
		?>

        <div id="doofinder-for-wp-additional-messages" class="doofinder-for-wp-additional-messages">
        </div>

		<?php
	}

	/**
	 * Render the error message that will be displayed if indexing error occurs.
	 */
	private function render_html_indexing_error() {
		?>

        <p id="doofinder-for-wp-indexing-error" class="doofinder-for-wp-indexing-error">
			<?php _e( 'An error occurred when indexing posts. Maybe the Doofinder API is down, but maybe it\'s just a temporary hiccup. Try refreshing and resuming indexing in a few minutes. Don\'t worry - posts that have already been indexed will not be lost.', 'doofinder_for_wp' ); ?>
        </p>

		<?php
	}

	/**
	 * Render HTML of indexing button.
	 *
	 * Clicking this button starts the process of indexing the posts.
	 */
	private function render_html_index_button() {
		$status   = $this->indexing_data->get( 'status' );
		$disabled = Settings::is_configuration_complete() ? '' : 'disabled';

		$buttonText = __( 'Index all content', 'doofinder_for_wp' );
		switch ( $status ) {
			case 'processing':
				$buttonText = __( 'Resume', 'doofinder_for_wp' );
				break;

			case 'completed':
				$buttonText = __( 'Reindex All', 'doofinder_for_wp' );
		}

		?>

        <p><strong><?php _e( 'WARNING:', 'doofinder_for_wp' ); ?></strong></p>
        <ul class="custom-list">
            <li><?php _e( 'This process will delete and reindex all data in Doofinder servers. It won\'t delete anything in your database.', 'doofinder_for_wp' ); ?></li>
            <li><?php _e( 'Indexing can take some time and search could return no results while indexing.', 'doofinder_for_wp' ); ?></li>
            <li><?php _e( 'You can switch internal search off before launching this process and enable it again when it finishes.', 'doofinder_for_wp' ); ?></li>
            <li><?php _e( 'Don\'t leave this page until the process finishes to ensure all data is properly indexed.', 'doofinder_for_wp' ); ?></li>
        </ul>

        <button
                type="button"
                id="doofinder-for-wp-index-button"
                class="button button-primary"
			<?php echo $disabled; ?>
        >
			<?php echo $buttonText; ?>
        </button>

        <div id="doofinder-for-wp-spinner" class="doofinder-for-wp-spinner spinner"></div>

		<?php
	}
}
