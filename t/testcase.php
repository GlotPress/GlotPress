<?php
require_once '../gp-includes/backpress/class.bp-sql-schema-parser.php';
require_once '../gp-includes/schema.php';
require_once '../gp-includes/install-upgrade.php';

class GP_UnitTestCase extends PHPUnit_Framework_TestCase {
    
	function setUp() {
		global $gpdb;
		error_reporting(E_ALL);
		// TODO: drop all tables, on most hosts users can't drop their databases
		$gpdb->query("DROP DATABASE ".GPDB_NAME.";");
		$gpdb->query("CREATE DATABASE ".GPDB_NAME.";");
		$gpdb->select( GPDB_NAME, $gpdb->dbh );
		gp_install();
		wp_cache_flush();
		ini_set('display_errors', 1);
    }

	function temp_filename() {
		$tmp_dir = '';
		$dirs = array('TMP', 'TMPDIR', 'TEMP');
		foreach($dirs as $dir)
			if (isset($_ENV[$dir]) && !empty($_ENV[$dir])) {
				$tmp_dir = $dir;
				break;
			}
		if (empty($dir)) $dir = '/tmp';
		$dir = realpath($dir);
		return tempnam($dir, 'testpomo');
	}
}