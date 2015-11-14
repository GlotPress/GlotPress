<?php

$per_page = get_user_option( 'gp_per_page' );
if ( 0 == $per_page ) {
	$per_page = 15;
}

$default_sort = get_user_option( 'gp_default_sort' );
if ( ! is_array( $default_sort ) ) {
	$default_sort = array(
		'by'  => 'priority',
		'how' => 'desc'
	);
}
?>
	<table class="form-table">
		<tr>
			<th><label for="gp_items_per_page"><?php _e( "Number of items per page:" ); ?></label></th>
			<td><input type="number" id="gp_items_per_page" name="gp_items_per_page" value="<?php echo $per_page ?>"/></td>
		</tr>
		<tr>
			<th><label for="gp_default_sort[by]"><?php _e("Default Sort By:") ?></label></th>
			<td><?php echo gp_radio_buttons('gp_default_sort[by]',
		array(
			'original_date_added' => __('Date added (original)'),
			'translation_date_added' => __('Date added (translation)'),
			'original' => __('Original string'),
			'translation' => __('Translation'),
			'priority' => __('Priority'),
			'references' => __('Filename in source'),
			'random' => __('Random'),
		), gp_array_get( $default_sort, 'by', 'priority' ) ); ?></td>
		</tr>
		<tr>
			<th><label for="gp_default_sort[how]"><?php _e("Default Sort Order:") ?></label></th>
			<td><?php echo gp_radio_buttons('gp_default_sort[how]',
				array(
					'asc' => __('Ascending'),
					'desc' => __('Descending'),
				), gp_array_get( $default_sort, 'how', 'desc' ) );
			?></td>
		</tr>
	</table>

