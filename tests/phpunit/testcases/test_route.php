<?php

class GP_Test_Route extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route';

	function test_headers_for_download_function_includes_double_quote_around_file_name() {
		$this->route->headers_for_download( 'test.po' );
		
		$this->assertEquals( ' attachment; filename="test.po"', $this->route->headers['Content-Disposition'] );
	}
}
