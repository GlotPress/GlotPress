<?php
require_once '../gp-includes/backpress/class.bp-sql-schema-parser.php';
require_once '../gp-includes/schema.php';
require_once '../gp-includes/install-upgrade.php';

class GP_UnitTestCase extends PHPUnit_Framework_TestCase {
    
	function setUp() {
		global $gpdb, $wp_auth_object, $wp_users_object;
		error_reporting( E_ALL );
		ini_set('display_errors', 1);
		if ( !gp_const_get( 'GP_IS_TEST_DB_INSTALLED' ) ) {
			$gpdb->query( 'DROP DATABASE '.GPDB_NAME.";" );
			$gpdb->query( 'CREATE DATABASE '.GPDB_NAME.";" );
			$gpdb->select( GPDB_NAME, $gpdb->dbh );
			add_filter( 'gp_schema_pre_charset', array( &$this, 'force_innodb' ) );
			gp_install();
			define( 'GP_IS_TEST_DB_INSTALLED', true );
		} else {
			// cleanup the global scope after previous tests
			$wp_users_object = new WP_Users( $gpdb );
			$wp_auth_object->users = $wp_users_object;
		}
		$gpdb->query( 'SET autocommit = 0;' );
		$gpdb->query( 'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;' );
		$gpdb->query( 'START TRANSACTION;' );
		wp_cache_flush();
		ini_set( 'display_errors', 1 );
    }

	function tearDown() {
		global $gpdb;
		$gpdb->query( 'ROLLBACK' );
	}

	function force_innodb( $schema ) {
		foreach( $schema as &$sql ) {
			$sql = str_replace( ');', ') TYPE=InnoDB;', $sql );
		}
		return $schema;
	}

	function temp_filename() {
		$tmp_dir = '';
		$dirs = array( 'TMP', 'TMPDIR', 'TEMP' );
		foreach( $dirs as $dir )
			if ( isset( $_ENV[$dir] ) && !empty( $_ENV[$dir] ) ) {
				$tmp_dir = $dir;
				break;
			}
		if (empty($dir)) $dir = '/tmp';
		$dir = realpath( $dir );
		return tempnam( $dir, 'testpomo' );
	}
}