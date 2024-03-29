<?php
/**
 * Templates: Delete Translation Set
 *
 * @package GlotPress
 * @subpackage Templates
 * @since 2.0.0
 */

gp_title(
	sprintf(
		/* translators: 1: Translation set name. 2: Project name. */
		__( 'Delete Translation Set &lt; %1$s &lt; %2$s &lt; GlotPress', 'glotpress' ),
		$set->name,
		$project->name
	)
);
gp_breadcrumb_project(
	$project,
	array(
		gp_link_get( $url, $locale->english_name . 'default' !== $set->slug ? ' ' . $set->name : '' ),
		__( 'Delete', 'glotpress' ),
	)
);
gp_tmpl_header();
?>
<h2><?php _e( 'Delete Translation Set', 'glotpress' ); ?></h2>
<form action="" method="post">
	<p>
		<?php _e( 'Note this will delete all translations associated with this set!', 'glotpress' ); ?>
	</p>
	<div class="button-group">
		<input class="button is-destructive" type="submit" name="submit" value="<?php esc_attr_e( 'Delete', 'glotpress' ); ?>" id="submit" />
		<a class="button is-link" href="<?php echo esc_url( gp_url_project_locale( $project, $locale->slug, $set->slug ) ); ?>"><?php _e( 'Cancel', 'glotpress' ); ?></a>
	</div>
	<?php gp_route_nonce_field( 'delete-translation-set_' . $set->id ); ?>
</form>
<?php
gp_tmpl_footer();
