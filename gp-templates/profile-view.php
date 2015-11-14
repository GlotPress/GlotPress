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
			<th><?php _e( "Number of items per page:" ); ?></label></th>
			<td><?php echo $per_page ?></td>
		</tr>
		<tr>
			<th><?php _e("Default Sort By:") ?></label></th>
			<td><?php $sort_by  = array(
			'original_date_added' => __('Date added (original)'),
			'translation_date_added' => __('Date added (translation)'),
			'original' => __('Original string'),
			'translation' => __('Translation'),
			'priority' => __('Priority'),
			'references' => __('Filename in source'),
			'random' => __('Random'),
		);

		echo $sort_by[ gp_array_get( $default_sort, 'by', 'priority' ) ];

		?></td>
		</tr>
		<tr>
			<th><?php _e("Default Sort Order:") ?></label></th>
			<td><?php $sort_order = array(
					'asc' => __('Ascending'),
					'desc' => __('Descending'),
				);
				
			echo $sort_order[ gp_array_get( $default_sort, 'how', 'desc' ) ];
			?></td>
		</tr>
	</table>

