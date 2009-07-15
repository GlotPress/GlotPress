<?php
require_once 'PHPUnit/Framework.php';

$tests_dir = dirname( __FILE__ );
$old_cwd = getcwd();
chdir( $tests_dir );

foreach( glob("test_*.php") as $test_file ) {
	include_once $test_file;
}

class all {
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();
		$gp_test_classes = array_filter( get_declared_classes(), create_function('$c', 'return preg_match("/^GP_Test_/", $c);'));
		foreach( $gp_test_classes as $class ) {
			$suite->addTestSuite( $class );
		}
		
        return $suite;
    }
}