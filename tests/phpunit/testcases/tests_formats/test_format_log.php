<?php

class GP_Test_Format_Log extends GP_UnitTestCase {

	/**
	 * @var Testable_GP_Format_Log
	 */
	private $format;

	function setUp(): void {
		parent::setUp();
		$this->format = new Testable_GP_Format_Log();
	}

	function test_sanitize_for_log_collapses_whitespace() {
		$this->assertEquals( 'one two', $this->format->sanitize_for_log( "one\ntwo" ) );
		$this->assertEquals( 'one two', $this->format->sanitize_for_log( "one\r\ntwo" ) );
		$this->assertEquals( 'one two', $this->format->sanitize_for_log( "one\rtwo" ) );
		$this->assertEquals( 'one two', $this->format->sanitize_for_log( "one \t\n two" ) );
	}

	function test_sanitize_for_log_leaves_a_plain_value_untouched() {
		$this->assertEquals( 'some.key', $this->format->sanitize_for_log( 'some.key' ) );
		$this->assertEquals( '', $this->format->sanitize_for_log( '' ) );
		$this->assertEquals( '', $this->format->sanitize_for_log( null ) );
	}

	function test_sanitize_for_log_caps_the_length() {
		$sanitized = $this->format->sanitize_for_log( str_repeat( 'a', 250 ) );

		$this->assertEquals( str_repeat( 'a', GP_Format::LOG_VALUE_MAX_LENGTH ) . '…', $sanitized );
	}

	function test_sanitize_for_log_counts_multibyte_characters_not_bytes() {
		$sanitized = $this->format->sanitize_for_log( str_repeat( 'ä', 250 ) );

		$this->assertEquals( str_repeat( 'ä', GP_Format::LOG_VALUE_MAX_LENGTH ) . '…', $sanitized );
	}

	function test_sanitize_for_log_handles_invalid_utf8() {
		$invalid = "one\ntwo" . chr( 0xC3 );

		$this->assertEquals( 'one two' . chr( 0xC3 ), $this->format->sanitize_for_log( $invalid ) );
	}

	function test_sanitize_for_log_caps_the_length_of_invalid_utf8() {
		$invalid   = chr( 0xC3 ) . str_repeat( 'a', 250 );
		$sanitized = $this->format->sanitize_for_log( $invalid );

		$this->assertEquals( GP_Format::LOG_VALUE_MAX_LENGTH + 3, strlen( $sanitized ) );
		$this->assertEquals( substr( $invalid, 0, GP_Format::LOG_VALUE_MAX_LENGTH ) . '...', $sanitized );
	}
}

/**
 * Class used to test private/protected and/or override methods.
 *
 * @method string sanitize_for_log( string $value )
 */
class Testable_GP_Format_Log extends GP_Format {

	/**
	 * List of private/protected methods.
	 *
	 * @var array
	 */
	private $non_accessible_methods = array(
		'sanitize_for_log',
	);

	/**
	 * Make private/protected methods reachable for tests.
	 *
	 * @param string $name      Method to call.
	 * @param array  $arguments Arguments to pass when calling.
	 * @return mixed|bool Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( in_array( $name, $this->non_accessible_methods, true ) ) {
			return $this->$name( ...$arguments );
		}
		return false;
	}

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		return '';
	}

	public function read_originals_from_file( $file_name ) {
		return false;
	}
}
