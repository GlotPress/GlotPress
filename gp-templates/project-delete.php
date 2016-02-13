<?php
gp_title( sprintf( __( 'Delete Project %s &lt; GlotPress', 'glotpress' ),  $project->name ) );
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2><?php echo wptexturize( sprintf( __( 'Delete project "%s"', 'glotpress' ), esc_html( $project->name ) ) ); ?></h2>
<form action="" method="post">
	<p>
		<?php _e( 'Note this will delete all translations, translation sets and child projects!', 'glotpress' ); ?>
	</p>
	<p>
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Delete', 'glotpress' ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="javascript:history.back();"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
</form>
<?php gp_tmpl_footer();
