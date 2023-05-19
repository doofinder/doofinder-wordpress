<?php

namespace Doofinder\WP\Multilanguage;

class WPML extends Language_Plugin {

	/**
	 * @inheritdoc
	 */
	public function get_languages() {
		if ( ! function_exists( 'icl_get_languages' ) ) {
			return array();
		}

		// "wpml_active_languages" filters the list of the
		// languages enabled (active) for a site.
		$languages = apply_filters( 'wpml_active_languages', null, 'orderby=code&order=desc' );

		if ( empty( $languages ) ) {
			return array();
		}

		// Create associative array with lang code / lang name pairs.
		// For example 'en' => 'English'.
		$formatted_languages = array();
		foreach ( $languages as $key => $value ) {
			$formatted_languages[ $key ] = $value['translated_name'];
		}

		return $formatted_languages;
	}

	/**
	 * @inheritdoc
	 */
	public function get_active_language() {
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			// WPML allows us to select "All languages"./
			// Let's treat it as no language selected.
			if ( ICL_LANGUAGE_CODE === 'all' ) {
				return '';
			}

			return ICL_LANGUAGE_CODE;
		}

		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function get_base_language() {
		global $sitepress;

		return $sitepress->get_default_language();
	}

	/**
	 * @inheritdoc
	 */
	public function get_posts_ids( $language_code, $post_type) {
		global $wpdb;

		$query = "
			SELECT element_id
			FROM {$wpdb->prefix}icl_translations
			WHERE {$wpdb->prefix}icl_translations.language_code = '$language_code'
			AND {$wpdb->prefix}icl_translations.element_type = 'post_{$post_type}'
			ORDER BY {$wpdb->prefix}icl_translations.element_id
		";

		$ids = $wpdb->get_results( $query, ARRAY_N );

		if ( ! $ids ) {
			return array();
		}

		return array_map( function ( $item ) {
			return $item[0];
		}, $ids );
	}
}
