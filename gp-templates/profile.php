<?php
gp_title( __('Profile &lt; GlotPress') );
gp_breadcrumb( array( __('Profile') ) );
gp_tmpl_header();

$per_page = GP::$user->current()->get_meta('per_page');
if ( 0 == $per_page )
	$per_page = 15;

$default_sort = GP::$user->current()->get_meta('default_sort');
if ( ! is_array($default_sort) ) {
	$default_sort = array(
		'by' => 'priority',
		'how' => 'desc'
	);
}
?>
<h2><?php _e( "Profile" ); ?></h2>
<form action="" method="post">
	<table class="form-table">
		<tr>
			<th><label for="per_page"><?php _e( "Number of items per page:" ); ?></label></th>
			<td><input type="number" id="per_page" name="per_page" value="<?php echo $per_page ?>"/></td>
		</tr>
		<tr>
			<th><label for="default_sort[by]"><?php _e("Default Sort By:") ?></label></th>
			<td><?php echo gp_radio_buttons('default_sort[by]',
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
			<th><label for="default_sort[how]"><?php _e("Default Sort Order:") ?></label></th>
			<td><?php echo gp_radio_buttons('default_sort[how]',
				array(
					'asc' => __('Ascending'),
					'desc' => __('Descending'),
				), gp_array_get( $default_sort, 'how', 'desc' ) );
			?></td>
		</tr>
	</table>
	<br>
	<input type="submit" name="submit" value="<?php esc_attr_e("Change Settings"); ?>">
</form>

<?php gp_tmpl_footer();
