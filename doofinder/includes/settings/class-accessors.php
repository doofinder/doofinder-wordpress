<?php

namespace Doofinder\WP\Settings;

use Doofinder\WP\Multilanguage\Multilanguage;

defined( 'ABSPATH' ) or die();

/**
 * Contains all methods used to retrieve or save option values.
 */
trait Accessors {

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
	 * Retrieve the API Host.
	 *
	 * Just an alias for "get_option" to avoid repeating the string
	 * (option name) in multiple files.
	 *
	 * @return string
	 */
	public static function get_api_host() {
		return get_option( 'doofinder_for_wp_api_host' );
	}

	/**
	 * Set the value of the API Host.
	 *
	 * Just an alias for "update_option" to avoid repeating the string
	 * (option name) in multiple files.
	 *
	 * @param string $api_key
	 */
	public static function set_api_host( $api_key ) {
		update_option( 'doofinder_for_wp_api_host', $api_key );
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
	 * Retrieve the chosen update on save option.
	 *
	 * Just an alias for "get_option" to avoid repeating the string
	 * (option name) in multiple files.
	 *
	 * @param string $language Language code to retrieve the hash for.
	 *
	 * @return string
	 */
	public static function get_update_on_save( $language = '' ) {
		return get_option( self::option_name_for_language(
			'doofinder_for_wp_update_on_save',
			$language
		) );
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
	 * Determine if we should grab JS layer directly from Doofinder.
	 *
	 * Just an alias for "get_option", because ideally we don't
	 * want to replace the option name in multiple files.
	 *
	 * @param string $language Language code.
	 *
	 * @return bool
	 */
	public static function is_js_layer_from_doofinder_enabled( $language = '' ) {
		return (bool) get_option( self::option_name_for_language(
			'doofinder_for_wp_load_js_layer_from_doofinder',
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
}
