<?php
/**
 * Templates: Delete Project
 *
 * @package GlotPress
 * @subpackage Templates
 * @since 2.0.0
 */

gp_title( sprintf( __( 'Delete Project %s &lt; GlotPress', 'glotpress' ),  $project->name ) );
gp_breadcrumb_project( $project );
gp_tmpl_header();
?>
<h2><?php printf( __( 'Delete project &#8220;%s&#8221;', 'glotpress' ), esc_html( $project->name ) ); ?></h2>
<form action="" method="post">
	<p>
		<?php _e( 'Note this will delete all translations, translation sets and child projects!', 'glotpress' ); ?>
	</p>
	<p>
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Delete', 'glotpress' ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="<?php echo esc_url( gp_url_project( $project ) ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
	<?php gp_route_nonce_field( 'delete-project_' . $project->id ); ?>
</form>
<?php gp_tmpl_footer();
