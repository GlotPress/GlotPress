<?php
gp_title( sprintf( __( 'Mass-create Translation Sets &lt; %s &lt; GlotPress', 'glotpress' ),  $project->name ) );
gp_breadcrumb_project( $project );
gp_enqueue_script( 'gp-mass-create-sets-page' );
wp_localize_script( 'gp-mass-create-sets-page', '$gp_mass_create_sets_options', array(
	'url' => gp_url_join( gp_url_current(), 'preview' ),
	'loading' => __( 'Loading translation sets to create&hellip;', 'glotpress' ),
));
gp_tmpl_header();
?>
<h2><?php _e( 'Mass-create Translation Sets', 'glotpress' ); ?></h2>
<p><?php _e( 'Here you can mass-create translation sets in this project.
The list of translation sets will be mirrored with the sets of a project you choose.
Usually this is one of the parent projects.', 'glotpress' ); ?></p>
<form action="<?php echo esc_url( gp_url_current() ); ?>" method="post">
	<dl>
		<dt><label for="project_id"><?php _e( 'Project to take translation sets from:', 'glotpress' );  ?></label></dt>
		<dd><?php echo gp_projects_dropdown( 'project_id', null ); ?></dd>
	</dl>
	<div id="preview"></div>
	<p><input type="submit" name="submit" value="<?php esc_attr_e( 'Create Translation Sets', 'glotpress' ); ?>" id="submit" /></p>
	<?php gp_route_nonce_field( 'mass-create-transation-sets_' . $project->id ); ?>
</form>
<?php gp_tmpl_footer();
