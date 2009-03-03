<?php
require 'simpletest/autorun.php';

define( 'GP_CONFIG_FILE', 't/unittests-config.php' );
define( 'GP_NO_ROUTING', true );
require_once '../gp-load.php';
require_once '../gp-includes/backpress/class.bp-sql-schema-parser.php';
require_once '../gp-includes/schema.php';
require_once '../gp-includes/install-upgrade.php';

// TODO: drop all tables, on most hosts users can't drop their databases
$gpdb->query("DROP DATABASE ".GPDB_NAME.";");
$gpdb->query("CREATE DATABASE ".GPDB_NAME.";");
$gpdb->select( GPDB_NAME, $gpdb->dbh );
gp_install();

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
