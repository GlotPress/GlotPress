<?php
gp_title( sprintf( __( 'Mass-create Translation Sets &lt; %s &lt; GlotPress' ),  $project->name ) );
gp_breadcrumb_project( $project );
wp_enqueue_script( 'mass-create-sets-page' );
wp_localize_script( 'mass-create-sets-page', '$gp_mass_create_sets_options', array(
	'url' => gp_url_join( gp_url_current(), 'preview'),
	'loading' => __('Loading translation sets to create&hellip;'),
));
gp_tmpl_header();
?>
<h2><?php _e('Mass-create Translation Sets'); ?></h2>
<p><?php _e('Here you can mass-create translation sets in this project.
The list of translation sets will be mirrored with the sets of a project you choose.
Usually this is one of the parent projects.'); ?></p>
<form action="<?php echo esc_url( gp_url_current() ); ?>" method="post">
	<dl>
		<dt><label for="project_id"><?php _e('Project to take translation sets from:');  ?></label></dt>
		<dd><?php echo gp_projects_dropdown( 'project_id', null ); ?></dd>
	</dl>
	<div id="preview"></div>
	<p><input type="submit" name="submit" value="<?php echo esc_attr( __('Create Translation Sets') ); ?>" id="submit" /></p>
</form>
<?php gp_tmpl_footer();