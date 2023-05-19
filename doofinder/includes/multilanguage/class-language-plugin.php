<?php

namespace Doofinder\WP\Multilanguage;

abstract class Language_Plugin {

	/**
	 * Get all languages.
	 *
	 * @return array[string]string List of all languages.
	 */
	abstract public function get_languages();

	/**
	 * Get active language code.
	 *
	 * @return string Lang code of current selected language.
	 */
	abstract public function get_active_language();

	/**
	 * Retrieve the base language of the site.
	 *
	 * This is important because the behavior of the site (e.g. language-specific
	 * option names) should be the same as if there was no multilanguage plugin
	 * installed.
	 *
	 * @return string Lang code of the base (primary) language of the site.
	 */
	abstract public function get_base_language();

	/**
	 * Get all posts ids of a given language.
	 *
	 * @param string $language_code
	 * @param string $post_type
	 *
	 * @return int[] List of ids.
	 */
	abstract public function get_posts_ids( $language_code, $post_type);

	/**
	 * Retrieve the name of the wordpress option
	 * for the current languages.
	 *
	 * Some fields in Doofinder settings will have different values,
	 * depending on language.
	 *
	 * @param string $base
	 *
	 * @return string
	 */
	public function get_option_name( $base ) {
		$language_code = $this->get_active_language();
		if ( ! $language_code ) {
			return $base;
		}

		$base_language = $this->get_base_language();
		if ( $language_code === $base_language ) {
			return $base;
		}

		return "{$base}_{$language_code}";
	}
}