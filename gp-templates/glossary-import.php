<?php
gp_title( __('Import into Glossary &lt; GlotPress') );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ), $translation_set->name ),
	gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ) . '/glossary', __('Glossary') ),
	__('import')
) );
gp_tmpl_header();
?>

<h2><?php _e( 'Import Glossary Entries'); ?></h2>
<p>
	<?php printf( __( 'Use this form to bulk upload glossary entries. The entries should be stored in a CSV file, matching the custom glossary format from <a href="%s">Google Translator Toolkit</a>.' ), 'https://support.google.com/translate/toolkit/answer/147854' ); ?><br/>
</p>

<form action="" method="post" enctype="multipart/form-data">
	<p>
		<label for="import-file"><?php _e('Import File:'); ?></label>
		<input type="file" name="import-file" id="import-file" />
	</p>
	<p><input type="submit" value="<?php echo esc_attr( __('Import') ); ?>"></p>
</form>

<?php gp_tmpl_footer();