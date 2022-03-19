<?php
gp_title(
	sprintf(
		/* translators: %s: project name */
		__( 'Edit project "%s" &lt; GlotPress', 'glotpress' ),
		$project->name
	)
);
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2>
	<?php
	printf(
		/* translators: %s: project name */
		__( 'Edit project "%s"', 'glotpress' ),
		esc_html( $project->name )
	);
	?>
</h2>
<form action="" method="post">
	<?php gp_tmpl_load( 'project-form', get_defined_vars() ); ?>

	<div class="button-group">
		<input class="button is-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Save', 'glotpress' ); ?>" id="submit" />
		<a class="button is-link" href="<?php echo esc_url( gp_url_project( $project ) ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a>
	</div>

	<?php gp_route_nonce_field( 'edit-project_' . $project->id ); ?>
</form>
<?php
gp_tmpl_footer();
