<?php

// WP-CLI is not loaded in the test suite, so provide the parent class the command extends.
if ( ! class_exists( 'WP_CLI_Command' ) ) {
	abstract class WP_CLI_Command {}
}

require_once GP_PATH . GP_INC . 'cli/translation-set.php';

class GP_Test_CLI_Translation_Set extends GP_UnitTestCase {

	function test_get_translation_set_does_not_create_dynamic_properties() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$command = new GP_CLI_Translation_Set();
		$method  = new ReflectionMethod( $command, 'get_translation_set' );
		$method->setAccessible( true );

		$deprecations = array();
		set_error_handler(
			function ( $errno, $errstr ) use ( &$deprecations ) {
				$deprecations[] = $errstr;
				return true;
			},
			E_DEPRECATED
		);

		try {
			$result = $method->invoke( $command, $set->project->path, $set->locale, $set->slug );
		} finally {
			restore_error_handler();
		}

		$this->assertInstanceOf( GP_Translation_Set::class, $result );
		$this->assertSame( $set->id, $result->id );
		$this->assertSame( array(), $deprecations );
	}
}
