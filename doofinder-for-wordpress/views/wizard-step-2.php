<?php

namespace Doofinder\WP;

$post_types     = Post_Types::instance();
$all_post_types = $post_types->get();

?>

<form method="post" action="<?php echo admin_url(); ?>">
    <input type="hidden" name="post-types-submitted" value="true">

	<?php _e( 'Select post type you want to index:', 'doofinder_for_wp' ); ?>

    <!-- Default language -->
    <div class="list-of-checkboxes">
		<?php

		// Selected post types are in $_POST.
		// If there's no POST, or user chose nothing, then
		// "post" and "page" should be selected by default.
		if ( isset( $_POST['post-types-to-index'] ) && $_POST['post-types-to-index'] ) {
			$selected_post_types = array_keys( $_POST['post-types-to-index'] );
		} else {
			$selected_post_types = Settings::get_post_types_to_index();
		}

		// If we are visiting this page for the first time, select
		// "page" and "post" by default.
		if ( ! $selected_post_types && ! isset( $_POST['post-types-submitted'] ) ) {
			$selected_post_types = array( 'post', 'page' );
		}

		foreach ( $all_post_types as $post_type ):
			// Is the post type selected?
			$checked = false;
			if ( in_array( $post_type, $selected_post_types ) ) {
				$checked = true;
			}

			?>

            <label class="has-checkbox">
                <input type="checkbox" name="post-types-to-index[<?php echo $post_type; ?>]"
					<?php if ( $checked ): ?>
                        checked
					<?php endif; ?>
                >

				<?php

				// Display full name of the post type
				$post_type_object = get_post_type_object( $post_type );
				echo $post_type_object->labels->name;

				?>
            </label>

		<?php endforeach; ?>
    </div>

	<?php

	// Display error message if the user didn't select any post types.
	if ( $this->get_error( 'post-types-to-index' ) ):
		?>

        <div class="error"><?php echo $this->get_error( 'post-types-to-index' ); ?></div>

	<?php endif; ?>

    <!-- All other languages -->
	<?php

	if ( $this->language->get_languages() ):
		foreach ( $this->language->get_languages() as $language_code => $language_name ):
			// Don't display another input for the base language.
			// The input above handles it.
			if ( $language_code === $this->language->get_base_language() ) {
				continue;
			}

			$selected_post_types = Settings::get_post_types_to_index( $language_code );

			// "page" and "post" are selected by default.
			if ( ! $selected_post_types && ! isset( $_POST['post-types-submitted'] ) ) {
				$selected_post_types = array( 'post', 'page' );
			}

			?>

            <h2><?php echo "$language_name:"; ?></h2>

            <div class="list-of-checkboxes">
				<?php

				foreach ( $all_post_types as $post_type ):
					// Is the post type selected?
					$checked = false;
					if ( in_array( $post_type, $selected_post_types ) ) {
						$checked = true;
					}

					?>

                    <label class="has-checkbox">
                        <input
                                type="checkbox"
                                name="post-types-to-index-<?php echo $language_code; ?>[<?php echo $post_type; ?>]"

							<?php if ( $checked ): ?>
                                checked
							<?php endif; ?>
                        >

						<?php

						// Display full name of the post type
						$post_type_object = get_post_type_object( $post_type );
						echo $post_type_object->labels->name;

						?>
                    </label>

				<?php endforeach; ?>
            </div>

		<?php

		endforeach;
	endif;

	?>

    <button type="submit"><?php _e( 'Next', 'woocommerce_for_wp' ); ?></button>
</form>
