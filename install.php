<?php
/**
 * Landing point for GlotPress installation
 */

define('GP_INSTALLING', true);
require_once( 'gp-load.php' );
require_once( BACKPRESS_PATH . 'class.bp-sql-schema-parser.php' );
require_once( GP_PATH . GP_INC . 'schema.php' );

function gp_update_db_version() {
	gp_update_option( 'gp_db_version', gp_get_option( 'gp_db_version' ) );
}

/**
 * Guesses the final installed URI based on the location of the install script
 *
 * @return string The guessed URI
 */
function guess_uri()
{
	$schema = 'http://';
	if ( isset( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on' ) {
		$schema = 'https://';
	}
	$uri = preg_replace( '|/[^/]*$|i', '/', $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

	return rtrim( $uri, " \t\n\r\0\x0B/" ) . '/';
}


$alterations = BP_SQL_Schema_Parser::delta( $gpdb, gp_schema_get() );
$messages = $alterations['messages'];
$errors = $alterations['errors'];

if ( !$errors ) {
	gp_update_db_version();
	gp_update_option( 'uri', guess_uri() );
}

// TODO: check if the .htaccess is in place or try to write it

$title = "Install GlotPress";
$path = gp_add_slash( gp_url_path() );
gp_tmpl_page( 'install',  get_defined_vars() );