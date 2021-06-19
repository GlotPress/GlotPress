<?php
/**
 * All code related to the GlotPress admin functions in the WordPress backend.
 *
 * @package GlotPress
 * @since 3.0.0
 */

/**
 * Class to add a settings page to the WordPress admin settings menu and other admin related functions.
 *
 * @since 3.0.0
 */
class GP_Admin {

	/**
	 * GP_Admin constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'gp_admin_menu' ), 10, 1 );
	}

	/**
	 * Callback to add the GlotPress menu and register the settings.
	 *
	 * @since 3.0.0
	 */
	public function gp_admin_menu() {
		add_options_page(
			__( 'GlotPress', 'glotpress' ),
			__( 'GlotPress', 'glotpress' ),
			'manage_options',
			'gp_admin_menu',
			array( $this, 'gp_admin_page' )
		);
		register_setting( 'glotpress', 'gp_delete_on_uninstall' );
	}

	/**
	 * Callback to ouput the GlotPress admin page.
	 *
	 * @since 3.0.0
	 */
	public function gp_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		?>
		<div class="wrap">
			<h1><?php _e( 'GlotPress Settings', 'glotpress' ); ?></h1>

			<form method="post" action="options.php" novalidate="novalidate">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="gp_delete_on_uninstall"><?php _e( 'Delete data during uninstall', 'glotpress' ); ?></label></th>
						<td>
							<input name="gp_delete_on_uninstall" type="checkbox" id="gp_delete_on_uninstall" value="1"
									<?php checked( '1', get_option( 'gp_delete_on_uninstall' ) ); ?> />
									<?php _e( 'Selecting this option will remove all GlotPress data when you uninstall GlotPress from the plugins page.', 'glotpress' ); ?>
						</td>
					</tr>
				</table>

				<?php
				settings_fields( 'glotpress' );
				do_settings_sections( 'glotpress' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}

if ( is_admin() ) {
	GP::$admin = new GP_Admin();
}
