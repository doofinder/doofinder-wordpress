<?php

namespace Doofinder\WP\Multilanguage;

class No_Language_Plugin extends Language_Plugin {

	/**
	 * @inheritdoc
	 */
	public function get_languages() {
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function get_active_language() {
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function get_base_language() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function get_posts_ids( $language_code, $post_type, $ids_greater_than, $number_of_posts ) {
		global $wpdb;

		$query = "
			SELECT ID
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_type = '{$post_type}'
			AND $wpdb->posts.ID > $ids_greater_than
			ORDER BY $wpdb->posts.ID
			LIMIT $number_of_posts
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
