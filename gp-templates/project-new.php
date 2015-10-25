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
		<input type="submit" name="submit" value="<?php echo esc_attr( __( 'Create', 'glotpress' ) ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="javascript:history.back();"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
</form>
<?php gp_tmpl_footer();