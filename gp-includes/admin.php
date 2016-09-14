<?php
/**
 * All code related to the GlotPress admin functions in the WordPress backend.
 *
 * @package GlotPress
 * @since 2.2.0
 */

/**
 * Class to add a settings page to the WordPress admin settings menu and other admin related functions.
 *
 * @since 2.2.0
 */
class GP_Admin {

	/**
	 * Constructor function for the class.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		/**
		 * Add the GlotPress admin page to the WordPress settings menu.
		 *
		 * @since 2.2.0
		 */
		add_action( 'admin_menu', array( $this, 'gp_admin_menu' ), 10, 1 );
	}

	/**
	 * Callback to add the GlotPress menu and register the settings.
	 *
	 * @since 2.2.0
	 */
	public function gp_admin_menu() {
		add_options_page( __( 'GlotPress', 'glotpress' ), __( 'GlotPress', 'glotpress' ), 'manage_options', 'GP_ADMIN_MENU', array( $this, 'gp_admin_page' ) );

		register_setting( 'glotpress', 'gp_delete_on_uninstall' );
	}

	/**
	 * Callback to ouput the GlotPress admin page.
	 *
	 * @since 2.2.0
	 */
	public function gp_admin_page() {
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
				<td>
					<input name="gp_delete_on_uninstall" type="checkbox" id="gp_delete_on_uninstall" value="1" <?php checked( '1', get_option( 'gp_delete_on_uninstall' ) ); ?> />
					<p class="description" id="gp_delete_on_uninstall-description"><?php _e( 'WARNING: Selecting this option will remove all GlotPress data when you uninstall GlotPress from the plugins page.', 'glotpress' ) ?></p>
				</td>
			</tr>
		</table>

		<?php do_settings_sections( 'glotpress' ); ?>

		<?php submit_button(); ?>
	</form>

	<hr>

	<h1><?php _e( 'Additional Information', 'glotpress' ); ?></h1>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'URL to access GlotPress', 'glotpress' ) ?></th>
			<td><a href="<?php echo gp_url_public_root(); // WPCS: XSS ok. ?>"><?php echo gp_url_public_root(); // WPCS: XSS ok. ?></a></td>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'GlotPress Version', 'glotpress' ) ?></th>
			<td><?php echo GP_VERSION; // WPCS: XSS ok. ?></td>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'GlotPress Database Version', 'glotpress' ) ?></th>
			<td><?php echo get_option( 'gp_db_version' ); // WPCS: XSS ok. ?></td>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'WordPress Permalink structure', 'glotpress' ) ?></th>
			<td><?php
			$permalink = get_option( 'permalink_structure' );

			if ( ! $permalink ) {
				_e( '&#151; You are running an unsupported permalink structure.', 'glotpress' );
				echo '<br>' . PHP_EOL; // WPCS: XSS ok.
				printf( __( 'GlotPress requires a custom permalink structure to be enabled. Please go to <a href="%s">Permalink Settings</a> and enable an option other than Plain. ', 'glotpress' ), admin_url( 'options-permalink.php' ) ); // WPCS: XSS ok.
			} else {
				echo $permalink; // WPCS: XSS ok.
			}
			?></td>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'PHP Version', 'glotpress' ) ?></th>
			<td><?php echo phpversion(); // WPCS: XSS ok. ?></td>
			</td>
		</tr>
	</table>

	<hr>

	<h1><?php _e( 'About GlotPress', 'glotpress' ); ?></h1>
	<table class="form-table" style="max-width: 772px;">
		<tr valign="top">
			<td scope="row" align="center"><img src="<?php echo gp_url_img( 'banner-772x250.png' ); // WPCS: XSS ok. ?>"></td>
		</tr>

		<tr valign="top">
			<td scope="row" align="center"><p><?php printf( __( 'by %1$sThe GlotPress Team%2$s', 'glotpress' ), '<a href="https://glotpress.org">', '</a>' ); ?></p></td>
		</tr>

		<tr valign="top">
			<td scope="row" colspan="2"><h2><?php _e( 'Rate and Review at WordPress.org', 'glotpress' ); ?></h2></td>
		</tr>

		<tr valign="top">
			<td scope="row" colspan="2"><?php printf( __( 'Thanks for installing GlotPress, please feel free to submit a %1$srating and review%2$s over at WordPress.org.  Your feedback is greatly appreciated!', 'glotpress' ), '<a href="http://wordpress.org/support/view/plugin-reviews/glotpress" target="_blank">', '</a>' ); ?></td>
		</tr>

		<tr valign="top">
			<td scope="row" colspan="2"><h2><?php _e( 'Support', 'glotpress' ); ?></h2></td>
		</tr>

		<tr valign="top">
			<td scope="row" colspan="2">
				<p><?php _e( 'Here are a few things to do submitting a support request:', 'glotpress' ); ?></p>

				<ul style="list-style-type: disc; list-style-position: inside; padding-left: 25px;">
					<li><?php printf( __( 'Have you read the %1$sFAQs%2$s?', 'glotpress' ), '<a title="FAQs" href="https://wordpress.org/plugins/glotpress/faq/" target="_blank">', '</a>' ); ?></li>
					<li><?php printf( __( 'Have you search the %1$ssupport forum%2$s for a similar issue?', 'glotpress' ), '<a href="http://wordpress.org/support/plugin/glotpress" target="_blank">', '</a>' ); ?></li>
					<li><?php _e( 'Have you search the Internet for any error messages you are receiving?', 'glotpress' ); ?></li>
				</ul>

				<p><?php printf( __( 'Still not having any luck? Then please open a new thread on the %1$sWordPress.org support forum%2$s.', 'glotpress' ), '<a href="http://wordpress.org/support/plugin/glotpress" target="_blank">', '</a>' ); ?></p>

				<p><?php printf( __( 'Or if you have a bug or feature request visit %1$sthe issues list%2$s', 'glotpress' ), '<a href="https://github.com/GlotPress/GlotPress-WP/issues">', '</a>' ); ?></p>
			</td>
		</tr>
	</table>
</div>
<?php
	}
}

if ( is_admin() ) {
	GP::$admin = new GP_Admin();
}
