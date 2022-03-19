<?php
/**
 * Templates: Delete Glossary
 *
 * @package GlotPress
 * @subpackage Templates
 * @since 2.0.0
 */

gp_title( __( 'Delete glossary &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb(
	array(
		gp_project_links_from_root( $project ),
		gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ), $translation_set->name ),
		gp_link_get( gp_url_join( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ), '/glossary' ), __( 'Glossary', 'glotpress' ) ),
		__( 'Delete', 'glotpress' ),
	)
);
gp_tmpl_header();
?>

<h2><?php _e( 'Delete glossary', 'glotpress' ); ?></h2>

<form action="" method="post">
	<p>
		<?php _e( 'Note this will delete all entries associated with this glossary!', 'glotpress' ); ?>
	</p>

	<div class="button-group">
		<input class="button is-destructive" type="submit" name="submit" value="<?php esc_attr_e( 'Delete', 'glotpress' ); ?>" id="submit" />
		<a class="button is-link" href="<?php echo esc_url( gp_url_join( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ), '/glossary' ) ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a>
	</div>
	<?php gp_route_nonce_field( 'delete-glossary_' . $glossary->id ); ?>
</form>

<?php
gp_tmpl_footer();
