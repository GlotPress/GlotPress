<?php

class GP_Test_Route_Translation_Escaping extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Translation';

	/**
	 * The translations page must escape the translation set name it renders in the breadcrumb.
	 */
	function test_translations_page_escapes_translation_set_name() {
		$name = '<b>abc123</b>';
		$set  = $this->factory->translation_set->create_with_project_and_locale( array( 'name' => $name ) );

		$this->route->translations_get( $set->project->path, $set->locale, $set->slug );

		$this->assertStringNotContainsString( $name, $this->route->template_output );
		$this->assertStringContainsString( esc_html( $name ), $this->route->template_output );
	}

	/**
	 * The translations page shows an error for an invalid regular expression search.
	 */
	function test_translations_page_shows_an_error_for_an_invalid_regex_search() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$_GET['filters'] = array(
			'term'  => '(Activ',
			'regex' => 'yes',
		);

		$this->route->translations_get( $set->project->path, $set->locale, $set->slug );

		unset( $_GET['filters'] );

		$this->assertStringContainsString( '<div class="error">The search term is not a valid regular expression.</div>', $this->route->template_output );
	}
}
