<?php
gp_title( __( 'Create New Project &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb( array(
	__( 'Create New Project', 'glotpress' ),
) );
gp_tmpl_header();
?>
<h2><?php _e( 'Create New Project', 'glotpress' ); ?></h2>
<form action="" method="post">
<?php gp_tmpl_load( 'project-form', get_defined_vars()); ?>
	<p>
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Create', 'glotpress' ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="<?php echo esc_url( gp_url_public_root() ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
	<?php gp_route_nonce_field( 'add-project' ); ?>
</form>
<?php gp_tmpl_footer();
