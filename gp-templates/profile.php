<?php
gp_title( __( 'Profile &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb( array( __( 'Profile', 'glotpress' ) ) );
gp_tmpl_header();
$per_page = get_user_option( 'gp_per_page' );
if ( 0 == $per_page )
}
$default_sort = get_user_option( 'gp_default_sort' );
if ( ! is_array( $default_sort ) ) {
	$default_sort = array(
		'by'  => 'priority',
		'how' => 'desc'
	);
}
?>
<h2><?php _e( 'Profile', 'glotpress' ); ?></h2>
<form action="" method="post">
<?php 
			<th><label for="per_page"><?php _e( "Number of items per page:" ); ?></label></th>
include_once( dirname( __FILE__ ) . '/profile-edit.php' );
?>
			<th><label for="default_sort[by]"><?php _e("Default Sort By:") ?></label></th>
			'original_date_added' => __('Date added (original)'),
			'translation_date_added' => __('Date added (translation)'),
			'original' => __('Original string'),
			'translation' => __('Translation'),
			'priority' => __('Priority'),
			'references' => __('Filename in source'),
			'random' => __('Random'),
			<th><label for="default_sort[how]"><?php _e("Default Sort Order:") ?></label></th>
					'asc' => __('Ascending'),
					'desc' => __('Descending'),
	<br>
	<input type="submit" name="submit" value="<?php esc_attr_e( 'Change Settings', 'glotpress' ); ?>">
</form>
<?php
gp_tmpl_footer();
