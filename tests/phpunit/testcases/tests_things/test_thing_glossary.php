<?php

class GP_Test_Glossary extends GP_UnitTestCase {

	function test_empty_translation_set_id() {
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => '' ) );
		$verdict = $glossary->validate();

		$this->assertFalse( $verdict );
	}

	function test_by_set_id() {
		$glossary_1 = GP::$glossary->create_and_select( array( 'translation_set_id' => '1' ) );
		$glossary_2 = GP::$glossary->create_and_select( array( 'translation_set_id' => '2' ) );
		$new = GP::$glossary->by_set_id( '1' );
		$this->assertEquals( $glossary_1, $new );
		$this->assertNotEquals( $glossary_2, $new );
	}

	function test_by_set_or_parent_project() {
		$locale = $this->factory->locale->create( array( 'slug' => 'bg' ) );

		$root = $this->factory->project->create( array( 'name' => 'root' ) );
		$root_set = $this->factory->translation_set->create( array( 'project_id' => $root->id, 'locale' => $locale->slug ) );

		$sub = 	$this->factory->project->create( array( 'name' => 'sub', 'parent_project_id' => $root->id ) );
		$sub_set = $this->factory->translation_set->create( array( 'project_id' => $sub->id, 'locale' => $locale->slug ) );

		$subsub = 	$this->factory->project->create( array( 'name' => 'subsub', 'parent_project_id' => $sub->id ) );
		$subsub_set = $this->factory->translation_set->create( array( 'project_id' => $subsub->id, 'locale' => $locale->slug ) );

		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $root_set->id ) );

		$sub_glossary = GP::$glossary->by_set_or_parent_project( $sub_set, $sub );
		$this->assertEquals( $glossary, $sub_glossary );
		$this->assertEquals( $glossary->path(), $sub_glossary->path() );

		$subsub_glossary = GP::$glossary->by_set_or_parent_project( $subsub_set, $subsub );
		$this->assertEquals( $glossary, $subsub_glossary );
		$this->assertEquals( $glossary->path(), $subsub_glossary->path() );
	}
	
	function test_delete() {
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => '1' ) );
		$glossary->delete();
		$new = GP::$glossary->by_set_id( '1' );
		$this->assertNotEquals( $glossary, $new );
		
	}
}
