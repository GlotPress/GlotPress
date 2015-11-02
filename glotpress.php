<?php
/**
 * @package GlotPress
 */
/*
Plugin Name: GlotPress
Plugin URI: http://glotpress.org/
Description: Translation app.
Version: 0.1
Author: deliciousbrains
Author URI: http://deliciousbrains.com
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
 */
function gp_activate_plugin() {
    $admins = GP::$permission->find_one( array( 'action' => 'admin' ) );
    if ( ! $admins ) {
        GP::$permission->create( array( 'user_id' => get_current_user_id(), 'action' => 'admin' ) );
    }
    gp_register_roles();
}
register_activation_hook( GP_PLUGIN_FILE, 'gp_activate_plugin' );

// Load the plugin's translated strings
load_plugin_textdomain( 'glotpress', false, dirname( plugin_basename( GP_PLUGIN_FILE ) ) . '/languages/' );


function gp_register_roles() {
	global $wp_roles;

	if ( ! class_exists( 'WP_Roles' ) ) {
		return;
	}

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}
	// translator role
	add_role( 'translator', __( 'Translator', 'glotpress' ), array(
		'edit-translation-set' => true,
	) );
	// validator role
	add_role( 'validator', __( 'Validator', 'glotpress' ), array(
		'validate-translation-set' => true,
	) );
}


function gp_grant_translator_cap_on_register( $user_id ) {
	$user = get_user_by( 'id', $user_id );

	$user->add_role( 'translator' );
}
add_action( 'user_register', 'gp_grant_translator_cap_on_register' );
