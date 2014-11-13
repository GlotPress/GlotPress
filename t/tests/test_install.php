<?php

class GP_Test_Install extends GP_UnitTestCase {

	function test_guess_uri() {
		$_SERVER['HTTP_HOST']   = 'example.org';
		$_SERVER['SCRIPT_NAME'] = '/install.php';

		$this->assertEquals( 'http://example.org/', guess_uri() );

		$_SERVER['SCRIPT_NAME'] = '/glotpress/install.php';
		$this->assertEquals( 'http://example.org/glotpress/', guess_uri() );
	}

}