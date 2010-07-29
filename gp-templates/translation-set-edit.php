<?php
gp_title( sprintf( __( 'Edit Translation Set &lt; %s &lt; %s &lt; GlotPress' ), $set->name, $project->name ) );
gp_breadcrumb( array(
	gp_link_project_get( $project, $project->name ),
	gp_link_get( $url, $locale->english_name . 'default' != $set->slug? ' '.$set->name : '' ),
) );
gp_tmpl_header();
?>
<h2><?php _e( 'Edit Translation Set' ); ?></h2>
<form action="" method="post">
<?php gp_tmpl_load( 'translation-set-form', get_defined_vars()); ?>
	<p>
		<input type="submit" name="submit" value="<?php echo esc_attr( __('Save') ); ?>" id="submit" />
		<span class="or-cancel">or <a href="javascript:history.back();">Cancel</a></span>
	</p>
</form>
<?php gp_tmpl_footer();
