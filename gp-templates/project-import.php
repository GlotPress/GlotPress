<?php
gp_title( $kind == 'originals'?
 	sprintf( __( 'Import Originals &lt; %s &lt; GlotPress', 'glotpress' ), esc_html( $project->name ) ) :
	sprintf( __( 'Import Translations &lt; %s &lt; GlotPress', 'glotpress' ), esc_html( $project->name ) ) );
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
	foreach ( GP::$formats as $slug => $format ) {
		$format_options[$slug] = $format->name;
	}
	$format_dropdown = gp_select( 'format', $format_options, 'po' );
?>
	<dt><label	for="format"><?php _e( 'Format:', 'glotpress' ); ?></label></dt>
	<dd><?php echo $format_dropdown; ?></dd>
	<dt><input type="submit" value="<?php echo esc_attr__( 'Import', 'glotpress' ); ?>"></dt>
</form>

<?php gp_tmpl_footer();