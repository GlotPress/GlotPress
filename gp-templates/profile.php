<?php
gp_title( __( 'Profile &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb( array( __( 'Profile', 'glotpress' ) ) );
gp_tmpl_header();

$per_page = get_user_meta( get_current_user_id(), 'gp_per_page', true );
if ( 0 == $per_page )
	$per_page = 15;

$default_sort = get_user_meta( get_current_user_id(), 'gp_default_sort', true );
if ( ! is_array($default_sort) ) {
	$default_sort = array(
		'by' => 'priority',
		'how' => 'desc'
	);
}
?>
<h2><?php _e( 'Profile', 'glotpress' ); ?></h2>
<form action="" method="post">
	<table class="form-table">
		<tr>
			<th><label for="per_page"><?php _e( 'Number of items per page:', 'glotpress' ); ?></label></th>
			<td><input type="number" id="per_page" name="per_page" value="<?php echo $per_page ?>"/></td>
		</tr>
		<tr>
			<th><label for="default_sort[by]"><?php _e( 'Default Sort By:', 'glotpress' ) ?></label></th>
			<td><?php echo gp_radio_buttons('default_sort[by]',
		array(
			'original_date_added' => __( 'Date added (original)', 'glotpress' ),
			'translation_date_added' => __( 'Date added (translation)', 'glotpress' ),
			'original' => __( 'Original string', 'glotpress' ),
			'translation' => __( 'Translation', 'glotpress' ),
			'priority' => __( 'Priority', 'glotpress' ),
			'references' => __( 'Filename in source', 'glotpress' ),
			'random' => __( 'Random', 'glotpress' ),
		), gp_array_get( $default_sort, 'by', 'priority' ) ); ?></td>
		</tr>
		<tr>
			<th><label for="default_sort[how]"><?php _e( 'Default Sort Order:', 'glotpress' ) ?></label></th>
			<td><?php echo gp_radio_buttons('default_sort[how]',
				array(
					'asc' => __( 'Ascending', 'glotpress' ),
					'desc' => __( 'Descending', 'glotpress' ),
				), gp_array_get( $default_sort, 'how', 'desc' ) );
			?></td>
		</tr>
	</table>
	<br>
	<input type="submit" name="submit" value="<?php esc_attr_e( 'Change Settings', 'glotpress' ); ?>">
</form>

<?php gp_tmpl_footer();
