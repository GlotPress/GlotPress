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
