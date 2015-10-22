<?php
gp_title( sprintf( __( 'Edit Project %s &lt; GlotPress' ),  $project->name ) );
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2><?php echo wptexturize( sprintf( __('Edit project "%s"'), esc_html( $project->name ) ) ); ?></h2>
<form action="" method="post">
<?php gp_tmpl_load( 'project-form', get_defined_vars()); ?>
	<p>
		<input type="submit" name="submit" value="<?php echo esc_attr( __('Save') ); ?>" id="submit" />
		<span class="or-delete"><?php _e('or'); ?> <a href="<?php echo gp_url_project( $project ) . '/-delete'; ?>" onclick="return confirm('<?php _e('Are you sure you want to delete this project?');?>');"><?php _e('Delete'); ?></a></span>
		<span class="or-cancel"><?php _e('or'); ?> <a href="javascript:history.back();"><?php _e('Cancel'); ?></a></span>
	</p>
</form>
<?php gp_tmpl_footer();