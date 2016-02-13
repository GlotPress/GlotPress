<?php
gp_title( sprintf( __( 'Delete Translation Set &lt; %s &lt; %s &lt; GlotPress', 'glotpress' ), $set->name, $project->name ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( $url, $locale->english_name . 'default' != $set->slug? ' '.$set->name : '' ),
) );
gp_tmpl_header();
?>
<h2><?php _e( 'Delete Translation Set', 'glotpress' ); ?></h2>
<form action="" method="post">
	<p>
		<?php _e( 'Note this will delete all translations associated with this set!', 'glotpress' ); ?>
		<input type="hidden" name="translation_set" value="<?php echo $project->id; ?>" id="translation_set" />
	</p>
	<p>
		<input type="submit" name="submit" value="<?php esc_attr_e( 'Delete', 'glotpress' ); ?>" id="submit" />
		<span class="or-cancel">or <a href="javascript:history.back();"><?php esc_attr_e( 'Cancel', 'glotpress' ); ?></a></span>
	</p>
</form>
<?php gp_tmpl_footer();
