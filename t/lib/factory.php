<?php
class GP_UnitTest_Factory {
	function __construct() {
		$this->project = new GP_UnitTest_Factory_For_Project( $this );
		$this->original = new GP_UnitTest_Factory_For_Original( $this );
		$this->translation_set = new GP_UnitTest_Factory_For_Translation_Set( $this );
		$this->translation = new GP_UnitTest_Factory_For_Translation( $this );
		$this->user = new GP_UnitTest_Factory_For_User( $this );
		$this->locale = new GP_UnitTest_Factory_For_Locale( $this );
	}	
}

class GP_UnitTest_Factory_For_Project extends GP_UnitTest_Factory_For_Thing {
	function __construct( $factory = null, $thing = null ) {
		parent::__construct( $factory, $thing? $thing : new GP_Project );
		$this->default_generation_definitions = array(
			'name' => new GP_UnitTest_Generator_Sequence( 'Project %s' ),
			'description' => 'I am a project',
			'parent_project_id' => null,
			'slug' => false,
			'active' => 0,
		);
	}
}

class GP_UnitTest_Factory_For_User extends GP_UnitTest_Factory_For_Thing {
	function __construct( $factory = null, $thing = null ) {
		parent::__construct( $factory, $thing? $thing : new GP_User );
		$this->default_generation_definitions = array(
			'user_login' => new GP_UnitTest_Generator_Sequence( 'User %s' ),
			'user_pass' => 'a',
			'user_email' => new GP_UnitTest_Generator_Sequence( 'user_%s@example.org' ),
		);
	}
	
	function create_admin( $args = array() ) {
		$user = $this->create( $args );
		GP::$permission->create( array( 'user_id' => $user->id, 'action' => 'admin' ) );
		return $user;
	}	
}


class GP_UnitTest_Factory_For_Translation_Set extends GP_UnitTest_Factory_For_Thing {
	function __construct( $factory = null, $thing = null ) {
		parent::__construct( $factory, $thing? $thing : new GP_Translation_Set );
		$this->default_generation_definitions = array(
			'name' => new GP_UnitTest_Generator_Sequence( 'Translation Set %s' ),
			'slug' => 'default',
			'locale' => new GP_UnitTest_Generator_Locale_Name,
			'project_id' => 1,
		);
	}

	function create_with_project_and_locale( $args = array(), $project_args = array() ) {
		$locale = $this->factory->locale->create();
		$project = $this->factory->project->create( $project_args );
		$set = $this->create( array( 'project_id' => $project->id, 'locale' => $locale->slug ) + $args );
		$set->project = $project;
		$set->locale = $locale->slug;
		return $set;
	}
}

class GP_UnitTest_Factory_For_Original extends GP_UnitTest_Factory_For_Thing {
	function __construct( $factory = null, $thing = null ) {
		parent::__construct( $factory, $thing? $thing : $thing? $thing : new GP_Original );
		$this->default_generation_definitions = array(
			'singular' => new GP_UnitTest_Generator_Sequence( 'Original %s' ),
		);
	}
}

class GP_UnitTest_Factory_For_Translation extends GP_UnitTest_Factory_For_Thing {
	function __construct( $factory = null, $thing = null ) {
		parent::__construct( $factory, $thing? $thing : new GP_Translation );
		$this->default_generation_definitions = array(
			'translation_0' => new GP_UnitTest_Generator_Sequence( 'Translation %s' ),
		);
	}
	
	function create_with_original_for_translation_set( $set, $args = array() ) {
		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$translation = $this->create( array_merge( $args, array( 'original_id' => $original->id, 'translation_set_id' => $set->id ) ) );
		$translation->original = $original;
		$translation->translation_set = $set;
		return $translation;
	}
}

class GP_UnitTest_Factory_For_Locale extends GP_UnitTest_Factory_For_Thing {
	function __construct( $factory = null, $thing = null ) {
		$thing = (object)array( 'field_names' => array_keys( get_object_vars( $thing? $thing : new GP_Locale ) ) );
		parent::__construct( $factory, $thing );
		$this->default_generation_definitions = array(
			'slug' => new GP_UnitTest_Generator_Locale_Name,
			'english_name' => new GP_UnitTest_Generator_Sequence( 'Locale %s' ),
		);
	}
	
	function create( $args = array(), $generation_definitions = null ) {
		if ( is_null( $generation_definitions ) ) $generation_definitions = $this->default_generation_definitions;
		$generated_args = $this->generate_args( $args, $generation_definitions, $callbacks );
		$created = new GP_Locale( $generated_args );
		if ( $callbacks ) {
			$updated_fields = $this->apply_callbacks( $callbacks, $created );
			$created = new GP_Locale( $updated_fields );
		}
		$locales = &GP_Locales::instance();
		$locales->locales[$created->slug] = $created;
		return $created;
	}
}

class GP_UnitTest_Factory_For_Thing {
	
	var $default_generation_definitions;
	var $thing;
	var $factory;
	
	/**
	 * Creates a new factory, which will create objects of a specific Thing
	 * 
	 * @param object $factory GLobal factory that can be used to create other objects on the system
	 * @param object $thing Instance of a GP_Thing subclass. This factory will create objects of this type
	 * @param array $default_generation_definitions Defines what default values should the properties of the object have. The default values
	 * can be generators -- an object with next() method. There are some default generators: {@link GP_UnitTest_Generator_Sequence},
	 * {@link GP_UnitTest_Generator_Locale_Name}, {@link GP_UnitTest_Factory_Callback_After_Create}.
	 */
	function __construct( $factory, $thing, $default_generation_definitions = array() ) {
		$this->factory = $factory;
		$this->default_generation_definitions = $default_generation_definitions;
		$this->thing = $thing;
	}
	
	function create( $args = array(), $generation_definitions = null ) {
		if ( is_null( $generation_definitions ) ) $generation_definitions = $this->default_generation_definitions;
		$generated_args = $this->generate_args( $args, $generation_definitions, $callbacks );
		$created = $this->thing->create( $generated_args );
		if ( !$created || is_wp_error( $created ) ) return $created;
		if ( $callbacks ) {
			$updated_fields = $this->apply_callbacks( $callbacks, $created );
			$save_result = $created->save( $updated_fields );
			if ( !$save_result || is_wp_error( $save_result ) ) return $save_result;
		}
		return $created;
	}
	
	function generate_args( $args = array(), $generation_definitions = null, &$callbacks = null ) {
		$callbacks = array();
		if ( is_null( $generation_definitions ) ) $generation_definitions = $this->default_generation_definitions;
		foreach( $this->thing->field_names as $field_name ) {
			if ( !isset( $args[$field_name] ) ) {
				if ( !isset( $generation_definitions[$field_name] ) ) {
					continue;
				}
				$generator = $generation_definitions[$field_name];
				if ( is_scalar( $generator ) )
					$args[$field_name] = $generator;
				elseif ( is_object( $generator ) && method_exists( $generator, 'call' ) ) {
					$callbacks[$field_name] = $generator;
				} elseif ( is_object( $generator ) )
					$args[$field_name] = $generator->next();
				else
					return new WP_Error( 'invalid_argument', 'Factory default value should be either a scalar or an generator object.' );
				
			}
		}
		return $args;
	}
	
	function apply_callbacks( $callbacks, $created ) {
		$updated_fields = array();
		foreach( $callbacks as $field_name => $generator ) {
			$updated_fields[$field_name] = $generator->call( $created );
		}
		return $updated_fields;
	}
	
	function callback( $function ) {
		return new GP_UnitTest_Factory_Callback_After_Create( $function );
	}	
}

class GP_UnitTest_Generator_Sequence {
	var $next;
	var $template_string;
	
	function __construct( $template_string = '%s', $start = 1 ) {
		$this->next = $start;
		$this->template_string = $template_string;
	}
	
	function next() {
		$generated = sprintf( $this->template_string , $this->next );
		$this->next++;
		return $generated;
	}
}

class GP_UnitTest_Generator_Locale_Name extends GP_UnitTest_Generator_Sequence {
	function __construct() {
		parent::__construct( '%s', 'aa' );
	}
}

class GP_UnitTest_Factory_Callback_After_Create {
	var $callback;

	function __construct( $callback ) {
		$this->callback = $callback;
	}

	function call( $object ) {
		return call_user_func( $this->callback, $object );
	}
}