<?php
gp_title( __( 'Import into Glossary &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb(
	array(
		gp_project_links_from_root( $project ),
		gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ), $translation_set->name ),
		gp_link_get( gp_url_project_locale( $project->path, $locale->slug, $translation_set->slug ) . '/glossary', __( 'Glossary', 'glotpress' ) ),
		__( 'Import', 'glotpress' ),
	)
);
gp_tmpl_header();
?>

<h2><?php _e( 'Import Glossary Entries', 'glotpress' ); ?></h2>
<p>
	<?php
		printf(
			// translators: 1: URL to the GlotPress manual entry for glossaries.
			__( 'Use this form to bulk upload glossary entries. The entries should be stored in a CSV file with a custom glossary format. Read more in the <a href="%s">Glotpress manual</a>.', 'glotpress' ),
			'https://glotpress.blog/the-manual/glossaries/'
		);
	?>
	<br/>
</p>

<form action="" method="post" enctype="multipart/form-data">
	<p>
		<label for="import-file"><?php _e( 'Import File:', 'glotpress' ); ?></label>
		<input type="file" name="import-file" id="import-file" />
	</p>
	<?php if ( $can_edit ) : ?>
		<p>
			<label for="import-flush">
				<input type="checkbox" id="import-flush" name="import-flush" />
				<?php _e( 'Flush existing glossary. Warning: all existing glossary entries will be deleted!', 'glotpress' ); ?>
			</label>
		</p>
	<?php endif; ?>
	<p><input type="submit" value="<?php esc_attr_e( 'Import', 'glotpress' ); ?>"></p>
	<?php gp_route_nonce_field( 'import-glossary-entries_' . $project->path . $locale->slug . $translation_set->slug ); ?>
</form>

<?php
gp_tmpl_footer();
