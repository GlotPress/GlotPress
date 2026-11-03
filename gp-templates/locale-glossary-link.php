<?php
if ( $locale_glossary ) : ?>
	<a href="<?php echo esc_url( gp_url_join( gp_url( '/languages' ), $locale->slug, $set_slug, 'glossary' ) ); ?>" class="glossary-link"><?php _e( 'Locale Glossary', 'glotpress' ); ?></a>
<?php elseif ( $can_create_locale_glossary ) : ?>
	<form action="<?php echo esc_url( gp_url_join( gp_url( '/languages' ), $locale->slug, $set_slug, 'glossary', '-create' ) ); ?>" method="post" class="glossary-link">
		<?php gp_route_nonce_field( 'create-locale-glossary_' . $locale->slug . $set_slug ); ?>
		<button type="submit" class="button is-link"><?php _e( 'Create Locale Glossary', 'glotpress' ); ?></button>
	</form>
<?php
endif;
