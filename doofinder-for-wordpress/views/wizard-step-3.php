<?php

namespace Doofinder\WP;

/** @var Setup_Wizard $this */

?>

<form method="post" action="<?php echo admin_url(); ?>">
	<?php

    // If there's no plugin active we still need to process 1 language.
	$languages = $this->language->get_languages();
	if ( ! $languages ) {
		$languages[''] = '';
	}

	foreach ( $languages as $language_code => $language_name ):

		?>
		<?php

		// Header for all languages except the default one.
		if ( $language_code !== $this->language->get_base_language() ):
			?>

            <h2><?php echo "$language_name:" ?></h2>

		<?php endif; ?>

		<?php

		// Language code (suffix) for options.
		// Default language has no suffix, all other languages do.
		$options_suffix = '';
		$name_suffix    = '';
		if ( $language_code !== $this->language->get_base_language() ) {
			$options_suffix = $language_code;
			$name_suffix    = "-$language_code";
		}

		?>
        <div class="form-row">
            <label class="has-checkbox">
				<?php _e( 'Do you want to enable JS Layer?', 'doofinder_for_wp' ); ?>

                <input type="checkbox" name="enable-js-layer<?php echo $name_suffix; ?>"
					<?php if ( Settings::is_js_layer_enabled( $options_suffix ) ): ?>
                        checked
					<?php endif; ?>
                >
            </label>
        </div>

        <div class="form-row">
            <label for="js-layer-code"><?php _e( 'JS Layer code:', 'doofinder_for_wp' ); ?></label>
            <textarea name="js-layer-code<?php echo $name_suffix; ?>" id="js-layer-code" rows="16"><?php

				if ( Settings::get_js_layer( $options_suffix ) ) {
					echo Settings::get_js_layer( $options_suffix );
				}

				?></textarea>
        </div>

        <div class="form-row">
            <label class="has-checkbox">
				<?php _e( 'Do you want to enable Internal Search?', 'doofinder_for_wp' ); ?>

                <input type="checkbox" name="enable-internal-search<?php echo $name_suffix; ?>"
					<?php if ( Settings::is_internal_search_enabled( $options_suffix ) ): ?>
                        checked
					<?php endif; ?>
                >
            </label>
        </div>
	<?php endforeach; ?>

    <div>
        <button type="submit"><?php _e( 'Next', 'woocommerce_for_wp' ); ?></button>
    </div>
</form>
