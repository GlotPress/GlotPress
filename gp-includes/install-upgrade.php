<?php
/**
 * Guesses the final installed URI based on the location of the install script
 *
 * @return string The guessed URI
 */
function guess_uri()
{
	$schema = 'http://';
	if ( strtolower( gp_array_get( $_SERVER, 'HTTPS' ) ) == 'on' ) {
		$schema = 'https://';
	}

	if( gp_array_get( $_SERVER, 'DOCUMENT_URI' ) ) {
		$uri = preg_replace( '|/[^/]*$|i', '/', $schema . gp_array_get( $_SERVER, 'HTTP_HOST') . gp_array_get( $_SERVER, 'DOCUMENT_URI' ) );
	}
	else {
		$uri = $schema . gp_array_get( $_SERVER, 'HTTP_HOST');
	}

	return rtrim( $uri, " \t\n\r\0\x0B/" ) . '/';
}

function gp_update_db_version() {
	gp_update_option( 'gp_db_version', gp_get_option( 'gp_db_version' ) );
}

function gp_upgrade_db() {
	global $gpdb;

	$alterations = BP_SQL_Schema_Parser::delta( $gpdb, gp_schema_get() );
	$errors = $alterations['errors'];

	if ( $errors ) {
		return $errors;
	}

	gp_upgrade_data( gp_get_option_from_db( 'gp_db_version' ) );

	gp_update_db_version();
}

function gp_upgrade() {
	return gp_upgrade_db();
}

/**
 * Sets the rewrite rules
 *
 * @return bool Returns true on success and false on failure
 */
function gp_set_htaccess() {
	// The server doesn't support mod rewrite
	if ( ! apache_mod_loaded( 'mod_rewrite', true ) ) {
		//return false;
	}

	if ( file_exists( '.htaccess' ) && ! is_writeable( '.htaccess' ) ) {
		return false;
	}

	// check if the .htaccess is in place or try to write it
	$htaccess_file = @fopen( '.htaccess', 'c+' );

	//error opening htaccess, inform user!
	if ( false === $htaccess_file ) {
		return false;
	}

	//'# BEGIN GlotPress' not found, write the access rules
	if ( false === strpos( stream_get_contents( $htaccess_file ), '# BEGIN GlotPress' ) ) {
		fwrite( $htaccess_file, gp_mod_rewrite_rules() );
	}

	fclose( $htaccess_file );

	return true;
}

/**
 * Return the rewrite rules
 *
 * @return string Rewrite rules
 */
function gp_mod_rewrite_rules() {
	$path = gp_add_slash( gp_url_path() );

	return '
# BEGIN GlotPress
	<IfModule mod_rewrite.c>;
	RewriteEngine On
	RewriteBase ' . $path . '
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule . ' . $path . 'index.php [L]
	</IfModule>
# END GlotPress';
}



function gp_upgrade_data( $db_version ) {
	global $gpdb;
	if ( $db_version < 190 ) {
		$gpdb->query("UPDATE $gpdb->translations SET status = REPLACE(REPLACE(status, '-', ''), '+', '');");
	}
}

function gp_install() {
	global $gpdb;

	$errors = gp_upgrade_db();

	if ( $errors ) return $errors;

	gp_update_option( 'uri', guess_uri() );
}

function gp_create_initial_contents() {
	global $gpdb;
	$gpdb->insert( $gpdb->projects, array('name' => __('Sample'), 'slug' => __('sample'), 'description' => __('A Sample Project'), 'path' => __('sample') ) );
	$gpdb->insert( $gpdb->originals, array('project_id' => 1, 'singular' => __('GlotPress FTW'), 'comment' => __('FTW means For The Win'), 'context' => 'dashboard', 'references' => 'bigfile:666 little-dir/small-file.php:71' ) );
	$gpdb->insert( $gpdb->originals, array('project_id' => 1, 'singular' => __('A GlotPress'), 'plural' => __('Many GlotPresses') ) );

	$gpdb->insert( $gpdb->translation_sets, array( 'name' => __('My Translation'), 'slug' => __('my'), 'project_id' => 1, 'locale' => 'bg', ) );

	// TODO: ask the user for an e-mail -- borrow WordPress install process
	if ( !defined('CUSTOM_USER_TABLE') ) {
		$admin = GP::$user->create( array( 'user_login' => 'admin', 'user_pass' => 'a', 'user_email' => 'baba@baba.net' ) );
		GP::$permission->create( array( 'user_id' => $admin->id, 'action' => 'admin' ) );
	}
	// TODO: ask the user to choose an admin user if using custom table
}