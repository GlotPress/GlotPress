<?php

class GP_Test_Route_Glossary_Entry_Csv extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Glossary_Entry';

	function test_export_escapes_formula_leading_cells() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create( array( 'translation_set_id' => $set->id ) );

		GP::$glossary_entry->create(
			array(
				'glossary_id'    => $glossary->id,
				'term'           => 'security',
				'translation'    => '=danger',
				'part_of_speech' => 'noun',
				'comment'        => '@at',
				'last_edited_by' => 1,
			)
		);
		GP::$glossary_entry->create(
			array(
				'glossary_id'    => $glossary->id,
				'term'           => 'plus',
				'translation'    => '+plus',
				'part_of_speech' => 'noun',
				'comment'        => '-minus',
				'last_edited_by' => 1,
			)
		);

		ob_start();
		$this->route->export_glossary_entries_get( $set->project->path, $set->locale, $set->slug );
		$csv = ob_get_clean();

		// Cells beginning with =, +, -, or @ get a leading tab, which fputcsv()
		// keeps inside a quoted field so spreadsheets render them as text.
		$this->assertStringContainsString( "security,\"\t=danger\",noun,\"\t@at\"", $csv );
		$this->assertStringContainsString( "plus,\"\t+plus\",noun,\"\t-minus\"", $csv );

		// Values that do not begin with such a character are written unchanged.
		$this->assertStringNotContainsString( "\tsecurity", $csv );
		$this->assertStringNotContainsString( "\tplus", $csv );
	}

	function test_import_accepts_terms_with_non_word_character_boundaries() {
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create( array( 'translation_set_id' => $set->id ) );

		$file = $this->create_csv(
			$set->locale,
			array(
				array( 'are you sure...?', 'sigur ...?', 'expression', '' ),
				array( 'note:', 'nota:', 'noun', '' ),
				array( 'display [location]', 'exibir [local]', 'expression', '' ),
				array( 'bogus', 'bogus', 'not-a-part-of-speech', '' ),
			)
		);

		list( $count ) = $this->read_glossary_entries( $file, $glossary->id, $set->locale );

		// The three punctuation-edged terms import; the invalid part of speech row is still skipped.
		$this->assertSame( 3, $count );

		$entry = GP::$glossary_entry->find_one( array( 'glossary_id' => $glossary->id, 'term' => 'are you sure...?' ) );
		$this->assertSame( 'sigur ...?', $entry->translation );

		$entry = GP::$glossary_entry->find_one( array( 'glossary_id' => $glossary->id, 'term' => 'note:' ) );
		$this->assertSame( 'nota:', $entry->translation );

		$entry = GP::$glossary_entry->find_one( array( 'glossary_id' => $glossary->id, 'term' => 'display [location]' ) );
		$this->assertSame( 'exibir [local]', $entry->translation );

		$this->assertEmpty( GP::$glossary_entry->find_one( array( 'glossary_id' => $glossary->id, 'term' => 'bogus' ) ) );
	}

	function test_import_reports_the_skipped_entries() {
		wp_set_current_user( $this->factory->user->create() );

		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create( array( 'translation_set_id' => $set->id ) );

		// Created by another user, as the entries of a shared glossary usually are.
		GP::$glossary_entry->create(
			array(
				'glossary_id'    => $glossary->id,
				'term'           => 'existing',
				'translation'    => 'vorhanden',
				'part_of_speech' => 'noun',
				'comment'        => '',
				'last_edited_by' => $this->factory->user->create(),
			)
		);

		$rows = array(
			array( 'existing', 'vorhanden', 'noun', '' ),
			array( 'house', 'Haus', 'noun', '' ),
			array( 'Häuser', 'Häuser', 'noun', '' ),
			array( '', 'leer', 'noun', '' ),
			null,
			array( str_repeat( 'ä', 300 ), 'x', 'noun', '' ),
		);
		for ( $i = 1; $i <= 10; $i++ ) {
			$rows[] = array( "bogus $i", 'x', 'not-a-part-of-speech', '' );
		}

		list( $count, $skipped ) = $this->read_glossary_entries( $this->create_csv( $set->locale, $rows ), $glossary->id, $set->locale );

		$this->assertSame( 1, $count );
		$this->assertSame(
			array(
				'13 entries were skipped because they are invalid: "Häuser" (row 4), row 5, "' . str_repeat( 'ä', 50 ) . '…" (row 7), "bogus 1" (row 8), "bogus 2" (row 9), "bogus 3" (row 10), "bogus 4" (row 11), "bogus 5" (row 12), "bogus 6" (row 13), "bogus 7" (row 14), and 3 more.',
				'1 entry was skipped because it already exists.',
			),
			$skipped
		);
	}

	private function create_csv( $locale, $rows ) {
		$file = tempnam( sys_get_temp_dir(), 'gp-glossary' );
		$f    = fopen( $file, 'w' );
		fputcsv( $f, array( 'Term', $locale, 'Part of speech', 'Comments' ), ',', '"', '' );
		foreach ( $rows as $row ) {
			if ( null === $row ) {
				fwrite( $f, "\n" );
			} else {
				fputcsv( $f, $row, ',', '"', '' );
			}
		}
		fclose( $f );

		return $file;
	}

	private function read_glossary_entries( $file, $glossary_id, $locale ) {
		$method = new ReflectionMethod( GP_Route_Glossary_Entry::class, 'read_glossary_entries_from_file' );
		$method->setAccessible( true );
		$result = $method->invoke( new GP_Route_Glossary_Entry(), $file, $glossary_id, $locale );
		unlink( $file );

		return $result;
	}

	function test_import_reverses_the_formula_escape() {
		$route = new Testable_GP_Route_Glossary_Entry_Csv();

		// The tab added on export is stripped again on import.
		$this->assertSame( '=danger', $route->testable_unescape_csv_value( "\t=danger" ) );
		$this->assertSame( '-minus', $route->testable_unescape_csv_value( "\t-minus" ) );

		// A value round-trips unchanged through export and import.
		foreach ( array( '=danger', '+plus', '-minus', '@at', 'plain', '' ) as $value ) {
			$this->assertSame( $value, $route->testable_unescape_csv_value( $route->testable_escape_csv_value( $value ) ) );
		}

		// A tab that is not part of the export escaping is left in place.
		$this->assertSame( "\tplain", $route->testable_unescape_csv_value( "\tplain" ) );
	}
}

class Testable_GP_Route_Glossary_Entry_Csv extends GP_Route_Glossary_Entry {
	public function testable_escape_csv_value( $value ) {
		return $this->escape_csv_value( $value );
	}

	public function testable_unescape_csv_value( $value ) {
		return $this->unescape_csv_value( $value );
	}
}
