<?php
/**
 * Landing point for GlotPress installation
 */

define('GP_INSTALLING', true);
require_once( 'gp-load.php' );
require_once( BACKPRESS_PATH . 'class.bp-sql-schema-parser.php' );
require_once( GP_PATH . GP_INC . 'install-upgrade.php' );
require_once( GP_PATH . GP_INC . 'schema.php' );

$show_htaccess_instructions = false;
$action = 'upgrade';

if ( gp_get_option( 'gp_db_version' ) <= gp_get_option_from_db( 'gp_db_version' ) && ! isset( $_GET['force'] ) ) {
	$success_message = __( 'You already have the latest version, no need to upgrade!' );
	$errors = array();
}
else {
	if ( gp_is_installed() ) {
		$success_message = __( 'GlotPress was successully upgraded!' );
		$errors = gp_upgrade();
	}
	else {
		$success_message = __( 'GlotPress was successully installed!' );
		$errors = gp_install();
		$action = 'install';

		if ( ! $errors ) {
			gp_create_initial_contents();
		}
	}

	$show_htaccess_instructions = ! gp_set_htaccess() && empty( $errors );
}

gp_tmpl_load( 'install',  get_defined_vars() );