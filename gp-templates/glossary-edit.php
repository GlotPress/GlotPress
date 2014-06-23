<?php
gp_title( __('Edit Glossary &lt; GlotPress') );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ), $translation_set->name ),
	gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ) . '/glossary', __('Glossary') ),
	__('edit')
) );
gp_tmpl_header();
?>

<h2><?php _e( 'Edit Glossary'); ?></h2>

<form action="" method="post">
	<p>
		<label for="glossary-edit-description"><?php _e( 'Description' ); ?></label> <span class="ternary"><?php _e('can include HTML'); ?></span> <br/>
		<textarea class="glossary-description" id="glossary-edit-description" name="glossary[description]"><?php echo esc_html($glossary->description); ?></textarea>
	</p>

	<p>
		<input type="hidden" name="glossary[id]" value="<?php echo esc_attr( $glossary->id ); ?>"/>
		<input type="hidden" name="glossary[translation_set_id]" value="<?php echo esc_attr( $glossary->translation_set_id ); ?>"/>
		<input type="submit" name="submit" value="<?php echo esc_attr( __('Save') ); ?>" id="submit" />
		<span class="or-cancel"><?php _e('or'); ?> <a href="javascript:history.back();"><?php _e('Cancel'); ?></a></span>
	</p>
</form>

<?php gp_tmpl_footer();