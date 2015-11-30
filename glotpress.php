<?php
/**
 * @package GlotPress
 */
/*
Plugin Name: GlotPress
Plugin URI: https://wordpress.org/plugins/glotpress/
Description: GlotPress is a tool to help translators collaborate.
Version: 1.0-alpha
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

define( 'GP_VERSION', '1.0-alpha-1100' );
define( 'GP_DB_VERSION', '940' );
define( 'GP_ROUTING', true );
define( 'GP_PLUGIN_FILE', __FILE__ );
define( 'GP_PATH', dirname( __FILE__ ) . '/' );
define( 'GP_INC', 'gp-includes/' );

require_once GP_PATH . 'gp-settings.php';

/**
 * Perform necessary actions on activation
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

// Load the plugin's translated strings
load_plugin_textdomain( 'glotpress', false, dirname( plugin_basename( GP_PLUGIN_FILE ) ) . '/languages/' );
