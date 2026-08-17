<?php

/**
 * Security regression tests for XML external entity (XXE) handling in the
 * XML-based import formats (Android XML and RESX).
 *
 * Both `GP_Format_Android::read_originals_from_file()` and
 * `GP_Format_ResX::read_originals_from_file()` parse uploaded files with
 * `simplexml_load_string()` WITHOUT the `LIBXML_NOENT` flag. As a result an
 * external entity (`<!ENTITY x SYSTEM "file://...">`) is never resolved, so a
 * crafted import file cannot disclose local files (LFI) or reach internal
 * services (SSRF).
 *
 * These tests lock that behaviour in: if a future change ever adds
 * `LIBXML_NOENT` (a common, innocent-looking "fix" for entity/CDATA handling),
 * the default libxml loader would resolve `file://` entities and these tests
 * would fail immediately — surfacing the regression before it ships.
 *
 * Note: plain internal entities (`<!ENTITY x "literal">`) ARE substituted by
 * libxml regardless of `LIBXML_NOENT`. That is not a security boundary here —
 * the value is a literal the same user could have typed into the string
 * directly, with no file/network access — so it is intentionally not asserted.
 */
class GP_Test_Format_XXE extends GP_UnitTestCase {

	/**
	 * Distinctive marker written to the canary file; a successful external-entity
	 * expansion would leak this value into the parsed originals.
	 *
	 * @var string
	 */
	const CANARY = 'XXE_LOCAL_FILE_CANARY_9f8e7d6c5b4a';

	/**
	 * Absolute paths of temporary files created during a test, removed in tearDown().
	 *
	 * @var string[]
	 */
	private $temp_files = array();

	public function tearDown(): void {
		foreach ( $this->temp_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		$this->temp_files = array();

		parent::tearDown();
	}

	/**
	 * Writes content to a fresh temporary file and tracks it for cleanup.
	 *
	 * @param string $content File content.
	 * @return string Absolute path to the created file.
	 */
	private function make_temp_file( $content ) {
		$path               = tempnam( get_temp_dir(), 'gp_xxe_' );
		$this->temp_files[] = $path;
		file_put_contents( $path, $content );

		return $path;
	}

	/**
	 * Collects the singular strings of all read originals, keyed by context.
	 *
	 * @param Translations|false $translations Result of read_originals_from_file().
	 * @return array<string,string> Map of context => singular.
	 */
	private function singulars_by_context( $translations ) {
		$this->assertNotFalse( $translations, 'The crafted (but well-formed) XML should still parse successfully.' );

		$result = array();
		foreach ( $translations->entries as $entry ) {
			$result[ $entry->context ] = $entry->singular;
		}

		return $result;
	}

	public function test_android_import_does_not_disclose_local_files_via_external_entity() {
		$canary_file = $this->make_temp_file( self::CANARY );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<!DOCTYPE resources [ <!ENTITY xxe SYSTEM "file://' . $canary_file . '"> ]>' . "\n"
			. '<resources>' . "\n"
			. "\t" . '<string name="benign">benign-control-abc123</string>' . "\n"
			. "\t" . '<string name="attack">&xxe;</string>' . "\n"
			. '</resources>';

		$format    = new GP_Format_Android();
		$singulars = $this->singulars_by_context( $format->read_originals_from_file( $this->make_temp_file( $xml ) ) );

		// The parser ran and processed the benign sibling: proves the assertion below is not vacuous.
		$this->assertSame( 'benign-control-abc123', $singulars['benign'] ?? null, 'The benign control string should be imported normally.' );

		// The external entity must NOT have been resolved into the parsed originals.
		$this->assertStringNotContainsString( self::CANARY, (string) ( $singulars['attack'] ?? '' ), 'Local file contents leaked through an external entity.' );
		$this->assertStringNotContainsString( self::CANARY, implode( "\n", $singulars ), 'Local file contents leaked into the imported originals.' );
	}

	public function test_resx_import_does_not_disclose_local_files_via_external_entity() {
		$canary_file = $this->make_temp_file( self::CANARY );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<!DOCTYPE root [ <!ENTITY xxe SYSTEM "file://' . $canary_file . '"> ]>' . "\n"
			. '<root>' . "\n"
			. "\t" . '<data name="benign"><value>benign-control-abc123</value></data>' . "\n"
			. "\t" . '<data name="attack"><value>&xxe;</value></data>' . "\n"
			. '</root>';

		$format    = new GP_Format_ResX();
		$singulars = $this->singulars_by_context( $format->read_originals_from_file( $this->make_temp_file( $xml ) ) );

		$this->assertSame( 'benign-control-abc123', $singulars['benign'] ?? null, 'The benign control string should be imported normally.' );
		$this->assertStringNotContainsString( self::CANARY, (string) ( $singulars['attack'] ?? '' ), 'Local file contents leaked through an external entity.' );
		$this->assertStringNotContainsString( self::CANARY, implode( "\n", $singulars ), 'Local file contents leaked into the imported originals.' );
	}
}
