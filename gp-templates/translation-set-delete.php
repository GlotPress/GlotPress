<?php
/**
 * Templates: Delete Translation Set
 *
 * @package GlotPress
 * @subpackage Templates
 * @since 2.0.0
 */

gp_title( sprintf( __( 'Delete Translation Set &lt; %s &lt; %s &lt; GlotPress', 'glotpress' ), $set->name, $project->name ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( $url, $locale->english_name . ( 'default' !== $set->slug ? ' ' . $set->name : '' ) ),
) );
gp_tmpl_header();
?>
<h2><?php _e( 'Delete Translation Set', 'glotpress' ); ?></h2>
<form action="" method="post">
	<p>
		<?php _e( 'Note this will delete all translations associated with this set!', 'glotpress' ); ?>
	</p>
	<p>
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Delete', 'glotpress' ); ?>" id="submit" />
		<span class="or-cancel"><?php _e( 'or', 'glotpress' ); ?> <a href="<?php echo esc_url( gp_url_project_locale( $project, $locale->slug, $set->slug ) ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
	<?php gp_route_nonce_field( 'delete-translation-set_' . $set->id ); ?>
</form>
<?php gp_tmpl_footer();
