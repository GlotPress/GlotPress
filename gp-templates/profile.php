<?php
gp_title( __('Profile &lt; GlotPress') );
gp_breadcrumb( array( __('Profile') ) );
gp_tmpl_header();
?>
<h2><?php _e( "Profile" ); ?></h2>
<form action="" method="post">
<?php 

include_once( dirname( __FILE__ ) . '/profile-edit.php' );
?>
	<br>
	<input type="submit" name="submit" value="<?php esc_attr_e("Change Settings"); ?>">
</form>
<?php
gp_tmpl_footer();
