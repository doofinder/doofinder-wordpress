<?php

namespace Doofinder\WP\Multilanguage;

class Polylang extends Language_Plugin {

	/**
	 * There is a built in method "pll_the_languages" but it only works
	 * on the frontend. Even if we try to extract from it functionalities
	 * that just grab languages from DB it still fails on the backend
	 * because some functions are missing (due to Polylang including
	 * different files on the backend and different on the front).
	 *
	 * Therefore we have to grab the languages from the taxonomy terms manually.
	 *
	 * @inheritdoc
	 */
	public function get_languages() {
		$language_slugs = array();

		$languages = get_terms( array( 'taxonomy' => 'language' ) );
		foreach ( $languages as $language ) {
			$language_slugs[$language->slug] = $language->name;
		}

		return $language_slugs;
	}

	/**
	 * @inheritdoc
	 */
	public function get_active_language() {
		// Sometimes even if Polylang constant exists the function does not.
		if ( ! function_exists( 'pll_current_language' ) ) {
			return '';
		}

		// There's "Show all languages" option. When selected we want to treat
		// it as if no language was selected. Luckily Polylang already
		// returns empty string for it so we don't have to do anything else.
		return \pll_current_language();
	}

	/**
	 * @inheritdoc
	 */
	public function get_base_language() {
		return \pll_default_language();
	}

	/**
	 * Polylang stores languages as taxonomies. Now that creates a couple of
	 * difficulties:
	 * 1. We want to grab posts by post type, so we *must* query the posts table.
	 * 2. We want to query by language, and because it's a taxonomy the slug of the language
	 *    resides in terms table.
	 * 3. Terms table does not contain post ID, in fact the only way from posts to terms
	 *    table seems to be via term_relationships and term_taxonomy.
	 *
	 * So if we want to match posts by post type and terms data we need to make a four
	 * table join to get from one table to the other. Joy!
	 *
	 * @inheritdoc
	 */
	public function get_posts_ids( $language_code, $post_type, $ids_greater_than, $number_of_posts ) {
		global $wpdb;

		$query = $wpdb->get_results( "
			SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			JOIN $wpdb->term_relationships ON $wpdb->posts.ID = $wpdb->term_relationships.object_id
			JOIN $wpdb->term_taxonomy 
				ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id
			JOIN $wpdb->terms ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id
			WHERE $wpdb->term_taxonomy.taxonomy = 'language'
			AND $wpdb->terms.slug = '$language_code'
			AND $wpdb->posts.post_type = '$post_type'
			AND $wpdb->posts.ID > $ids_greater_than
			GROUP BY $wpdb->posts.ID
			ORDER BY $wpdb->posts.ID
			LIMIT $number_of_posts
		", ARRAY_N );

		return array_map( function ( $item ) {
			return $item[0];
		}, $query );
	}
}
