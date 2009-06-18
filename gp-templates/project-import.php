<?php
gp_title( $kind == 'originals'?
 	sprintf( __('Import Originals &lt; %s &lt; GlotPress'), gp_h( $project->name ) ) :
	sprintf( __('Import Translations &lt; %s &lt; GlotPress'), gp_h( $project->name ) ) );
gp_breadcrumb( array(
	gp_link_home_get(),
	gp_link_project_get( $project, $project->name ),
	$kind == 'originals'? __('Import originals') : __('Import translations'),
) );
gp_tmpl_header();
?>
<form action="" method="post" enctype="multipart/form-data">
	<p>
		<label for="mo-file"><?php echo __('MO file'); ?></label><input type="file" name="mo-file" id="mo-file" />
	</p>
	<p>
		<label for="pot-file"><?php echo __('PO/POT file'); ?></label><input type="file" name="pot-file" id="pot-file" />
	</p>

	<p><input type="submit" value="<?php echo gp_attr( __('Import') ); ?>"></p>
</form>
<?php gp_tmpl_footer(); ?>