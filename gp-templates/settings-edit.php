<?php
/**
 * The user settings block
 *
 * A single table that contains all of the user settings, which is included as part of gp-templates/settings.php.
 *
 * @link http://glotpress.org
 *
 * @package GlotPress
 * @since 2.0.0
 */

$per_page = (int) get_user_option( 'gp_per_page' );
if ( 0 === $per_page ) {
	$per_page = 15;
}

$default_sort = get_user_option( 'gp_default_sort' );
if ( ! is_array( $default_sort ) ) {
	$default_sort = array(
		'by'  => 'priority',
		'how' => 'desc',
	);
}
?>
	<table class="form-table">
		<tr>
			<th><label for="per_page"><?php _e( 'Number of items per page:', 'glotpress' ); ?></label></th>
			<td><input type="number" id="per_page" name="per_page" value="<?php echo $per_page; // WPCS: xss ok. ?>"/></td>
		</tr>
		<tr>
			<th><label for="default_sort[by]"><?php _e( 'Default Sort By:', 'glotpress' ) ?></label></th>
			<td><?php
				$sort_bys = wp_list_pluck( gp_get_sort_by_fields(), 'title' );

				echo gp_radio_buttons( 'default_sort[by]', $sort_bys, gp_array_get( $default_sort, 'by', 'priority' ) );
			?></td>
		</tr>
		<tr>
			<th><label for="default_sort[how]"><?php _e( 'Default Sort Order:', 'glotpress' ) ?></label></th>
			<td><?php
				echo gp_radio_buttons(
					'default_sort[how]',
					array(
						'asc' => __( 'Ascending', 'glotpress' ),
						'desc' => __( 'Descending', 'glotpress' ),
					),
					gp_array_get( $default_sort, 'how', 'desc' )
				);
			?></td>
		</tr>
	</table>

