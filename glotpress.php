<?php
/**
 * @package GlotPress
 */
/*
Plugin Name: GlotPress
Plugin URI: https://wordpress.org/plugins/glotpress/
Description: GlotPress is a tool to help translators collaborate.
Version: 1.0.1
Author: the GlotPress team
Author URI: http://glotpress.org
License: GPLv2 or later
Text Domain: glotpress
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define( 'GP_VERSION', '1.0.1' );
define( 'GP_DB_VERSION', '940' );
define( 'GP_ROUTING', true );
define( 'GP_PLUGIN_FILE', __FILE__ );
define( 'GP_PATH', dirname( __FILE__ ) . '/' );
define( 'GP_INC', 'gp-includes/' );
define( 'GP_WP_REQUIRED_VERSION', '4.4' );

// Load the plugin's translated strings
load_plugin_textdomain( 'glotpress' );

/**
 * Adds a message if an incompatible version of WordPress is running.
 *
 * Message is only displayed on the plugin screen.
 *
 * @since 1.0.0
 */
function gp_unsupported_version_admin_notice() {
	global $wp_version;

	$screen = get_current_screen();

	if ( 'plugins' !== $screen->id ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p style="max-width:800px;"><b><?php _e( 'GlotPress Disabled', 'glotpress' );?></b> <?php _e( '&#151; You are running an unsupported version of WordPress.', 'glotpress' ); ?></p>
		<p style="max-width:800px;"><?php
			printf(
				/* translators: 1: Required version of WordPress 2: Current version of WordPress */
				__( 'GlotPress requires WordPress %1$s or later and has detected you are running %2$s. Upgrade your WordPress install or deactivate the GlotPress plugin to remove this message.', 'glotpress' ),
				esc_html( GP_WP_REQUIRED_VERSION ),
				esc_html( $wp_version ) );
		?></p>
	</div>
	<?php
}

/*
 * Check the WP version, if we don't meet the minimum version to run GlotPress
 * return so we don't cause any errors and add show an admin notice.
 */
if ( version_compare( $GLOBALS['wp_version'], GP_WP_REQUIRED_VERSION, '<' ) ) {
	add_action( 'admin_notices', 'gp_unsupported_version_admin_notice', 10, 2 );

	// Bail out now so no additional code is run.
	return;
}

require_once GP_PATH . 'gp-settings.php';

/**
 * Perform necessary actions on activation.
 *
 * @since 1.0.0
 */
function gp_activate_plugin() {
	$admins = GP::$permission->find_one( array( 'action' => 'admin' ) );
	if ( ! $admins ) {
		GP::$permission->create( array( 'user_id' => get_current_user_id(), 'action' => 'admin' ) );
	}
}
register_activation_hook( GP_PLUGIN_FILE, 'gp_activate_plugin' );

/**
 * Run the plugin de-activation code.
 *
 * @since 1.0.0
 *
 * @param bool $network_wide Whether the plugin is deactivated for all sites in the network
 *                           or just the current site.
 */
function gp_deactivate_plugin( $network_wide ) {

	/*
	 * Flush the rewrite rule option so it will be re-generated next time the plugin is activated.
	 * If network deactivating, ensure we flush the option on every site.
	 */
	if ( $network_wide ) {
		$sites = wp_get_sites();

		foreach ( $sites as $site ) {
			switch_to_blog( $site['blog_id'] );
			update_option( 'gp_rewrite_rule', '' );
			restore_current_blog();
		}
	} else {
		update_option( 'gp_rewrite_rule', '' );
	}

}
register_deactivation_hook( GP_PLUGIN_FILE, 'gp_deactivate_plugin' );
