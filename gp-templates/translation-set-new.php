<?php
gp_title( __( 'Create New Translation Set &lt; GlotPress' ) );
gp_breadcrumb( array(
	__('Create New Translation Set'),
) );
gp_tmpl_header();
?>
<h2><?php _e( 'Create New Translation Set' ); ?></h2>
<form action="" method="post">
<?php gp_tmpl_load( 'translation-set-form', get_defined_vars()); ?>
	<p><input type="submit" name="submit" value="<?php echo esc_attr( __('Create') ); ?>" id="submit" /></p>
</form>
<?php gp_tmpl_footer();