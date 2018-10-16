<?php

class GP_Test_Misc extends GP_UnitTestCase {

	function test_gp_array_flatten() {
		$this->assertEquals( array(), gp_array_flatten( array() ) );
		$this->assertEquals( array( 1, 2, 3 ), gp_array_flatten( array( 1, array( 2, 3 ) ) ) );
		$this->assertEquals( array( 1, 2, 3, 4, 5, 6, 7 ), gp_array_flatten( array( 1, array( 2, array( 3, 4 ), 5, ), 6, array( 7 ) ) ) );
	}

	function test_gp_array_zip() {
		$this->assertEquals( array(), gp_array_zip() );
		$this->assertEquals( array(), gp_array_zip( array() ) );
		$this->assertEquals( array(), gp_array_zip( array(), array(), array() ) );
		$this->assertEquals( array( array('baba') ), gp_array_zip( array('baba') ) );
		$this->assertEquals( array(), gp_array_zip( array('baba'), array(), array() ) );
		$this->assertEquals( array( array('baba', 'dyado') ), gp_array_zip( array('baba'), array('dyado') ) );
		$this->assertEquals( array( array('baba', 'dyado') ), gp_array_zip( array('baba', 'boom'), array('dyado') ) );
		$this->assertEquals( array( array( array('baba'), 'dyado') ), gp_array_zip( array( array('baba'), 'boom'), array('dyado') ) );
	}

	function test_gp_array_any() {
		$this->assertEquals( false, gp_array_any( 'intval', array( 0 ) ) );
		$this->assertEquals( false, gp_array_any( '__return_false', array( 1, 2, 3, 4 ) ) );
		$this->assertEquals( false, gp_array_any( '__return_true', array() ) );
		$this->assertEquals( true, gp_array_any( '__return_true', array( 1, 2, 3, 4 ) ) );
		$this->assertEquals( true, gp_array_any( function ( $x ) {
			return $x % 2;
		}, array( 1, 2, 3, 4 ) ) );
	}

	function test_gp_object_has_var_returs_true_if_var_is_null() {
		$this->assertTrue( gp_object_has_var( (object)array( 'baba' => null), 'baba' ) );
	}

	function test_gp_get_import_file_format() {
		// Test to make sure we detect each file format extension correctly.
		foreach( GP::$formats as $format ) {
			foreach( $format->get_file_extensions() as $extension ) {
				$this->assertEquals( $format, gp_get_import_file_format( null, 'filename.' . $extension ) );
			}
		}

		// Test to make sure we don't auto detect if a known file format is passed in.
		$this->assertEquals( GP::$formats[ 'po' ], gp_get_import_file_format( 'po', 'filename.strings' ) );

		// Test to make sure we return null when no file format is found.
		$this->assertEquals( null, gp_get_import_file_format( 'thiswillneverbeafileformat', 'filename.thiswillneverbeafileformat' ) );
		$this->assertEquals( null, gp_get_import_file_format( null, 'filename.thiswillneverbeafileformat' ) );
	}
}
