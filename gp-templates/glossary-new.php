<?php
gp_title( __( 'Create New Glossary &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ), $translation_set->name ),
	__( 'Create Glossary', 'glotpress' )
) );
gp_tmpl_header();
?>

<h2><?php _e( 'Create New Glossary', 'glotpress' ); ?></h2>
<form action="" method="post">
	<p>
		<label for="glossary-new-description"><?php _e( 'Description (optional)', 'glotpress' ); ?></label> <span class="ternary"><?php _e( 'can include HTML', 'glotpress' ); ?></span><br/>
		<textarea class="glossary-description" id="glossary-new-description" name="glossary[description]"></textarea>
	</p>
	<p>
		<input type="hidden" name="glossary[translation_set_id]" value="<?php echo esc_attr( $glossary->translation_set_id ); ?>"/>
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Create', 'glotpress' ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="<?php echo gp_url_project_locale( $project, $locale->slug, $translation_set->slug ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
	<?php gp_route_nonce_field( 'add-glossary' ); ?>
</form>

<?php gp_tmpl_footer();
