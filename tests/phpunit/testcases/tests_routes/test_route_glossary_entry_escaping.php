<?php

class GP_Test_Route_Glossary_Entry_Escaping extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Glossary_Entry';

	/**
	 * The glossary view must escape the translation set name it renders in the breadcrumb.
	 */
	function test_glossary_view_escapes_translation_set_name() {
		$name = '<b>abc123</b>';
		$set  = $this->factory->translation_set->create_with_project_and_locale( array( 'name' => $name ) );
		GP::$glossary->create( array( 'translation_set_id' => $set->id ) );

		$this->route->glossary_entries_get( $set->project->path, $set->locale, $set->slug );

		$this->assertStringNotContainsString( $name, $this->route->template_output );
		$this->assertStringContainsString( esc_html( $name ), $this->route->template_output );
	}
}
