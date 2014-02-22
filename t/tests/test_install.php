<?php

class GP_Test_Install extends GP_UnitTestCase {

	function test_guess_uri() {
		$_SERVER['HTTP_HOST']   = 'example.org';
		$_SERVER['REQUEST_URI'] = '';

		$this->assertEquals( 'http://example.org/', guess_uri() );

		$_SERVER['DOCUMENT_URI'] = '/install.php';
		$this->assertEquals( 'http://example.org/', guess_uri() );
	}

}