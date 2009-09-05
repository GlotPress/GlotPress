<?php
gp_title( __( 'Create New Project &lt; GlotPress' ) );
gp_breadcrumb( array(
	gp_link_home_get(),
	__('Create New Project'),
) );
gp_tmpl_header();
?>
<h2><?php _e( 'Create New Project' ); ?></h2>
<form action="" method="post">
<?php gp_tmpl_load( 'project-form', get_defined_vars()); ?>
	<p><input type="submit" name="submit" value="<?php echo esc_attr( __('Create') ); ?>" id="submit" /></p>
</form>
<?php gp_tmpl_footer();