<?php
gp_title( __( 'Profile &lt; GlotPress', 'glotpress' ) );
gp_breadcrumb( array( __( 'Profile', 'glotpress' ) ) );
gp_tmpl_header();

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
<h2><?php _e( 'Profile', 'glotpress' ); ?></h2>
<form action="" method="post">
<?php 
include_once( dirname( __FILE__ ) . '/profile-edit.php' );
?>
	<br>
	<input type="submit" name="submit" value="<?php esc_attr_e( 'Change Settings', 'glotpress' ); ?>">
</form>

<?php gp_tmpl_footer();
