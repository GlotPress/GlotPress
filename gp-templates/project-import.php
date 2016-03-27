<?php
if ( 'originals' == $kind ) {
 	$title = sprintf( __( 'Import Originals &lt; %s &lt; GlotPress', 'glotpress' ), esc_html( $project->name ) );
	$return_link = gp_url_project( $project );
} else {
	$title = sprintf( __( 'Import Translations &lt; %s &lt; GlotPress', 'glotpress' ), esc_html( $project->name ) );
	$return_link = gp_url_project_locale( $project, $locale->slug, $translation_set->slug );
}

gp_title( $title );
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>

<h2><?php echo $kind == 'originals'? __( 'Import Originals', 'glotpress' ) : __( 'Import Translations', 'glotpress' ); ?></h2>
<form action="" method="post" enctype="multipart/form-data">
	<dl>
	<dt><label for="import-file"><?php _e( 'Import File:', 'glotpress' ); ?></label></dt>
	<dd><input type="file" name="import-file" id="import-file" /></dd>
<?php
	$format_options = array();
	$format_options[ 'auto' ] = __( 'Auto Detect', 'glotpress' );
	foreach ( GP::$formats as $slug => $format ) {
		$format_options[$slug] = $format->name;
	}
	$format_dropdown = gp_select( 'format', $format_options, 'auto' );
?>
	<dt><label	for="format"><?php _e( 'Format:', 'glotpress' ); ?></label></dt>
	<dd><?php echo $format_dropdown; ?></dd>
	<dt>
	<p>
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Import', 'glotpress' ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="<?php echo $return_link; ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
	</dt>
	</dl>
	<?php gp_route_nonce_field( ( 'originals' === $kind ? 'import-originals_' : 'import-translations_' ) . $project->id ); ?>
</form>

<?php gp_tmpl_footer();
