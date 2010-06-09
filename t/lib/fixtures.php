<?php

class GP_UnitTest_Fixtures {
	
	var $generic_locale_slug = 'en';
	
	function delete_all() {
		foreach( array( GP::$user, GP::$original, GP::$project, GP::$translation_set, GP::$permission ) as $thing ) {
			$thing->delete_all();
		}
		GP_UnitTestCase::clean_up_global_scope();
	}
			
	function load() {
		$this->delete_all();
		$this->load_projects();
		$this->load_users();
		$this->load_translation_sets();
	}
	
	function load_users() {
		$this->users = new stdClass;
		$this->users->admin = GP::$user->create( array( 'user_login' => 'admin', 'user_pass' => 'a', 'user_email' => 'admin@example.org' ) );
		GP::$permission->create( array( 'user_id' => $this->users->admin->id, 'action' => 'admin' ) );		
		$this->users->nobody = GP::$user->create( array( 'user_login' => 'nobody', 'user_pass' => 'a', 'user_email' => 'nobody@example.org' ) );
		$this->users->validator_for_generic = $this->create_validator_for( $this->projects->generic->id );
		$this->users->validator_for_generic2 = $this->create_validator_for( $this->projects->generic2->id );
	}
	
	function load_projects() {
		$this->projects = new stdClass;
		$this->projects->generic = $this->create_project_generic();
		$this->projects->generic2 = $this->create_project_generic();
	}
	
	function load_translation_sets() {
		$this->sets = new StdClass;
		$this->sets->default_in_generic = GP::$translation_set->create( array( 'name' => 'Default for Generic', 'slug' => 'default',
				'project_id' => $this->projects->generic->id, 'locale' => $this->generic_locale_slug ) );
		$this->sets->default_in_generic2 = GP::$translation_set->create( array( 'name' => 'Default for Generic 2', 'slug' => 'default',
				'project_id' => $this->projects->generic2->id, 'locale' => $this->generic_locale_slug ) );
	}
	
	function create_project_generic() {
		static $counter = 0;
		$counter++;
		return GP::$project->create( array( 'name' => 'Generic '.$counter, 'slug' => 'generic'.$counter, 'description' => 'A generic project '.$counter ) );
	}

	function create_validator_for( $project_id, $locale_slug = null ) {
		$user = GP::$user->create( array( 'user_login' => 'validator-for-'.$project_id, 'user_pass' => 'a', 'user_email' => "validatorfor{$project_id}@example.org" ) );
		$locale_slug = is_null( $locale_slug )? $this->generic_locale_slug : $locale_slug;
		GP::$validator_permission->create( array( 'user_id' => $user->id, 'action' => 'approve',
				'project_id' => $project_id, 'locale_slug' => $locale_slug, 'set_slug' => 'default', ) );
		return $user;
	}
}