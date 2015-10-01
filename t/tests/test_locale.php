<?php

class GP_Test_Locale extends GP_UnitTestCase {

	// Test if old property can be read when someone tries to read it.
	function test_rtl_old() {
		$locale = $this->factory->locale->create();
		$locale->text_direction = 'rtl';

		$this->assertTrue( isset( $locale->rtl ) );
		$this->assertTrue( $locale->rtl );
	}


}