<?php

class GP_Test_Format_SRT extends GP_UnitTestCase {

    function setUp() {
		parent::setUp();
		$this->srt = new GP_Format_SRT;
		$this->entries = array(
			array('00:20:41,150 --> 00:20:45,109', "- How did he do that?\n- Made him an offer he couldn't refuse.", "- Comment a-t-il fait qui?\n- Fait de lui une offre qu’il ne pouvait pas refuser.", ''),
			array('00:21:41,150 --> 00:21:45,109', "- What kind of offer exactly?\n- The kind where you end up at the bottom of a river if you don't choose right.", "- Quel type d’offre exactement?\n- Le genre où vous retrouver au fond d’une rivière, si vous ne choisissez pas droit.", ''),
			array('00:22:41,150 --> 00:22:45,109', "- Ugh, cement loafers? Really?\n- Yes, now let's get out of here.", "- Pouah, ciment mocassins? Vraiment?\n- Oui, maintenant nous allons sortir d’ici.", ''),
		);
	}

	function test_export() {
		$entries_for_export = array();

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;

			$entries_for_export[] = (object)array(
				'context' => $context,
				'singular' => $original,
				'translations' => array($translation),
			);
		}

		$file     = file_get_contents( GP_DIR_TESTDATA . '/translation.srt' );
		$exported = $this->srt->print_exported_file( 'p', 'l', 't', $entries_for_export );

		file_put_contents( "c:\\temp\\contents.srt", $exported );

		$this->assertEquals( $file, $exported );
	}

	function test_read_originals() {
		$translations = $this->srt->read_originals_from_file( GP_DIR_TESTDATA . '/originals.srt' );

		$this->assertEquals( count( $this->entries ), count( $translations->entries ), 'number of read originals is different from the expected' );

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;
			$found = false;
			foreach ( $translations->entries as $entry ) {
				if ( $entry->context !== $context ) {
					continue;
				}

				$found = true;
				$this->assertEquals( $original, $entry->singular );
				$this->assertEquals( $context, $entry->context );
			}

			$this->assertEquals( $found, true );
		}
	}

	function test_read_translations() {
		$stubbed_originals = array();

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;
			$stubbed_originals[] = new GP_Original( array( 'singular' => $original, 'context' => $context ) );
		}

		GP::$original = $this->getMock( 'GP_Original', array('by_project_id') );
		GP::$original->expects( $this->once() )
					->method( 'by_project_id' )
					->with( $this->equalTo(2) )
					->will( $this->returnValue($stubbed_originals) );

		$translations = $this->srt->read_translations_from_file( GP_DIR_TESTDATA . '/translation.srt', (object)array( 'id' => 2 ) );

		foreach ( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;
			$this->assertEquals( $translation, $translations->translate( $original, $context ) );
		}
	}
}
