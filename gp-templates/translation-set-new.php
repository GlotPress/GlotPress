<?php
gp_title( __( 'Create New Translation Set &lt; GlotPress', 'glotpress'  ) );
$project? gp_breadcrumb_project( $project ) : gp_breadcrumb( array( __( 'New Translation Set', 'glotpress' ) ) );
gp_tmpl_header();
?>
<h2><?php _e( 'Create New Translation Set', 'glotpress'  ); ?></h2>
<form action="" method="post">
<?php gp_tmpl_load( 'translation-set-form', get_defined_vars()); ?>
	<p>
		<input type="submit" name="submit" value="<?php echo esc_attr( __( 'Create', 'glotpress' ) ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="javascript:history.back();"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
</form>
<?php gp_tmpl_footer();