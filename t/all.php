<?php
require_once 'PHPUnit/Autoload.php';

$tests_dir = dirname( __FILE__ );
$old_cwd = getcwd();
chdir( $tests_dir );

for( $depth = 0; $depth <= 3; $depth++ ) {
	foreach( glob( str_repeat( 'tests_*/', $depth ) . 'test_*.php' ) as $test_file ) {
		include_once $test_file;
	}	
}

class all {
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite();
		foreach( get_declared_classes() as $class ) {
			if ( preg_match( '/^GP_Test_/', $class ) ) {
				$suite->addTestSuite( $class );
			}
		}
		
        return $suite;
    }
}

chdir( $old_cwd );