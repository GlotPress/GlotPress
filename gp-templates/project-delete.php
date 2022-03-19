<?php
/**
 * Templates: Delete Project
 *
 * @package GlotPress
 * @subpackage Templates
 * @since 2.0.0
 */

gp_title(
	sprintf(
		/* translators: %s: project name */
		__( 'Delete project "%s" &lt; GlotPress', 'glotpress' ),
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
		__( 'Delete project "%s"', 'glotpress' ),
		esc_html( $project->name )
	);
	?>
</h2>
<form action="" method="post">
	<p>
		<?php _e( 'Note this will delete all translations, translation sets and child projects!', 'glotpress' ); ?>
	</p>

	<div class="button-group">
		<input class="button is-destructive" type="submit" name="submit" value="<?php esc_attr_e( 'Delete', 'glotpress' ); ?>" id="submit" />
		<a class="button is-link" href="<?php echo esc_url( gp_url_project( $project ) ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a>
	</div>

	<?php gp_route_nonce_field( 'delete-project_' . $project->id ); ?>
</form>
<?php
gp_tmpl_footer();
