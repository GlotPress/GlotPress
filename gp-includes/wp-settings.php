<?php
/**
 * All code related to the GlotPress menu in the WordPress settings menu.
 *
 * @package GlotPress
 * @since 2.2.0
 */

/**
 * Add the GlotPress admin page to the WordPress settings menu.
 *
 * @since 2.2.0
 */
add_action( 'admin_menu', 'gp_admin_menu', 10, 1 );

/**
 * Callback to add the GlotPress menu and register the settings.
 *
 * @since 2.2.0
 */
function gp_admin_menu() {
	add_options_page( __( 'GlotPress', 'glotpress' ), __( 'GlotPress', 'glotpress' ), 'manage_options', basename( __FILE__ ), 'gp_admin_page' );

	register_setting( 'glotpress', 'gp_delete_on_uninstall' );
}

/**
 * Callback to ouput the GlotPress admin page.
 *
 * @since 2.2.0
 */
function gp_admin_page() {
	// If the current user can't manage options, display a message and return immediately.
	if ( ! current_user_can( 'manage_options' ) ) {
		_e( 'You do not have permissions to this page!', 'glotpress' );

		return;
	}
?>
<div class="wrap">
<h1><?php _e( 'GlotPress Settings', 'glotpress' ); ?></h1>

<form method="post" action="options.php" novalidate="novalidate">
<?php settings_fields( 'glotpress' ); ?>
<table class="form-table">
<tr>
<th scope="row"><label for="gp_delete_on_uninstall"><?php _e( 'Delete data during uninstall', 'glotpress' ) ?></label></th>
<td><input name="gp_delete_on_uninstall" type="checkbox" id="gp_delete_on_uninstall" value="1" <?php checked( '1', get_option( 'gp_delete_on_uninstall' ) ); ?> />
<p class="description" id="gp_delete_on_uninstall-description"><?php _e( 'WARNING: Selecting this option will remove all GlotPress data when you uninstall GlotPress from the plugins page.', 'glotpress' ) ?></p></td>
</td>
</tr>
</table>

<?php do_settings_sections( 'glotpress' ); ?>

<?php submit_button(); ?>
</form>

</div>
<?php
}
