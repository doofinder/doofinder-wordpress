<?php

namespace Doofinder\WP;

/** @var Setup_Wizard $this */

?>

<form method="post" action="<?php echo admin_url(); ?>">

    <!-- API Key -->
    <div class="form-row <?php if ( $this->get_error( 'api-key' ) ): ?>has-error<?php endif; ?>">
        <label for="api-key"><?php _e( 'Api Key:', 'doofinder_for_wp' ); ?></label>

        <input required type="text" name="api-key" id="api-key"
			<?php if ( Settings::get_api_key() ): ?>
                value="<?php echo Settings::get_api_key(); ?>"
			<?php endif; ?>
        >

		<?php if ( $this->get_error( 'api-key' ) ): ?>
            <div class="error"><?php echo $this->get_error( 'api-key' ); ?></div>
		<?php endif; ?>
    </div>

    <!-- Hash -->
    <div class="form-row <?php if ( $this->get_error( 'search-engine-hash' ) ): ?>has-error<?php endif; ?>">
        <label for="search-engine-hash"><?php _e( 'Search Engine Hash:', 'doofinder_for_wp' ); ?></label>

        <input required type="text" name="search-engine-hash" id="search-engine-hash"
			<?php if ( Settings::get_search_engine_hash() ): ?>
                value="<?php echo Settings::get_search_engine_hash(); ?>"
			<?php endif; ?>
        >

		<?php if ( $this->get_error( 'search-engine-hash' ) ): ?>
            <div class="error"><?php echo $this->get_error( 'search-engine-hash' ); ?></div>
		<?php endif; ?>
    </div>

    <!-- Hashes for all remaining languages -->
	<?php

	if ( $this->language->get_languages() ):
		foreach ( $this->language->get_languages() as $language_code => $language_name ):
			// Don't display another input for the base language.
			// The input above handles it.
			if ( $language_code === $this->language->get_base_language() ) {
				continue;
			}

			?>

            <div class="form-row">
                <label for="search-engine-hash-<?php echo $language_code; ?>">
					<?php printf( __( 'Search Engine Hash - %s:', 'doofinder_for_wp' ), $language_name ); ?>
                </label>

                <input
                        type="text"
                        name="search-engine-hash-<?php echo $language_code; ?>"
                        id="search-engine-hash-<?php echo $language_code; ?>"

					<?php if ( Settings::get_search_engine_hash( $language_code ) ): ?>
                        value="<?php echo Settings::get_search_engine_hash( $language_code ); ?>"
					<?php endif; ?>
                >
            </div>

		<?php

		endforeach;
	endif;

	?>

    <button type="submit"><?php _e( 'Next', 'doofinder_for_wp' ); ?></button>
</form>
