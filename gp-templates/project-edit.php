<?php
gp_title( sprintf( __( 'Edit Project %s &lt; GlotPress', 'glotpress' ),  $project->name ) );
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2><?php echo wptexturize( sprintf( __( 'Edit project "%s"', 'glotpress' ), esc_html( $project->name ) ) ); ?></h2>
<form action="" method="post">
<?php gp_tmpl_load( 'project-form', get_defined_vars()); ?>
	<p>
		<input type="submit" name="submit" value="<?php echo esc_attr( __( 'Save', 'glotpress' ) ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="javascript:history.back();"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
</form>
<?php gp_tmpl_footer();