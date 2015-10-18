<?php
require_once dirname( __FILE__ ) . '/../../gp-includes/backpress/class.bp-sql-schema-parser.php';
require_once dirname( __FILE__ ) . '/../../gp-includes/schema.php';
require_once dirname( __FILE__ ) . '/../../gp-includes/install-upgrade.php';

require_once dirname( __FILE__ ) . '/factory.php';

class GP_UnitTestCase extends PHPUnit_Framework_TestCase {

	protected $backupGlobalsBlacklist = array( 'gpdb' );

	var $url = 'http://example.org/';

	function setUp() {
		global $gpdb;
		$gpdb->suppress_errors = false;
		$gpdb->show_errors = false;

		if ( defined( 'GP_DEBUG' ) && GP_DEBUG ) {
			if ( defined( 'E_DEPRECATED' ) )
				error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
			else
				error_reporting( E_ALL );
		}

		ini_set('display_errors', 1);

		if ( !gp_const_get( 'GP_IS_TEST_DB_INSTALLED' ) ) {
			$gpdb->query( 'DROP DATABASE '.GPDB_NAME.";" );
			$gpdb->query( 'CREATE DATABASE '.GPDB_NAME.";" );
			$gpdb->select( GPDB_NAME, $gpdb->dbh );
			$gpdb->query( 'SET storage_engine = INNODB;' );
			$errors = gp_install();
			if ( $errors ) {
				gp_error_log_dump($errors);
				die( 'ERROR: gp_install() returned errors! Check the error_log for complete SQL error message' );
			}
			define( 'GP_IS_TEST_DB_INSTALLED', true );
		}
		$this->factory = new GP_UnitTest_Factory;
		$this->clean_up_global_scope();
		$this->start_transaction();
		ini_set( 'display_errors', 1 );
		$this->url_filter = returner( $this->url );
		add_filter( 'gp_get_option_uri', $this->url_filter );
    }

	function tearDown() {
		global $gpdb;
		$gpdb->query( 'ROLLBACK' );
		remove_filter( 'gp_get_option_uri', $this->url_filter );
	}

	function clean_up_global_scope() {
		GP::$user->reintialize_wp_users_object();
		$locales = &GP_Locales::instance();
		$locales->locales = array();
		$_GET = array();
		$_POST = array();
		$this->flush_cache();
		/**
		 * @todo re-initialize all thing objects
		 */
		GP::$translation_set = new GP_Translation_Set;
		GP::$original = new GP_Original;
	}

	function flush_cache() {
		global $wp_object_cache;
		$wp_object_cache->group_ops = array();
		$wp_object_cache->stats = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache = array();
		if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset();
		}
		wp_cache_flush();
		wp_cache_add_global_groups( array( 'users', 'userlogins', 'usermeta', 'usermail', 'usernicename' ) );
	}

	function start_transaction() {
		global $gpdb;
		$gpdb->query( 'SET autocommit = 0;' );
		$gpdb->query( 'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE;' );
		$gpdb->query( 'START TRANSACTION;' );
	}

	function temp_filename() {
		$tmp_dir = '';
		$dirs    = array( 'TMP', 'TMPDIR', 'TEMP' );

		foreach( $dirs as $dir ) {
			if ( isset( $_ENV[ $dir ] ) && ! empty( $_ENV[ $dir ] ) ) {
				$tmp_dir = $dir;
				break;
			}
		}

		if ( empty( $tmp_dir ) ) {
			$tmp_dir = '/tmp';
		}

		$tmp_dir = realpath( $tmp_dir );

		return tempnam( $tmp_dir, 'testpomo' );
	}

	function set_normal_user_as_current() {
		$user = $this->factory->user->create();
		$user->set_as_current();
		return $user;
	}

	function set_admin_user_as_current() {
		$admin = $this->factory->user->create_admin();
		$admin->set_as_current();
		return $admin;
	}

	function assertWPError( $actual, $message = '' ) {
		$this->assertTrue( is_wp_error( $actual ), $message );
	}

	function assertEqualFields( $object, $fields ) {
		foreach( $fields as $field_name => $field_value ) {
			if ( $object->$field_name != $field_value ) {
				$this->fail();
			}
		}
	}

	function assertEqualSets( $expected, $actual ) {
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	function assertDiscardWhitespace( $expected, $actual ) {
		$this->assertEquals( preg_replace( '/\s*/', '', $expected ), preg_replace( '/\s*/', '', $actual ) );
	}
}
