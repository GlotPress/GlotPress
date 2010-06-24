<?php
class GP_UnitTest_Factory {
	function __construct() {
		$this->project = new GP_UnitTest_Factory_For_Project;
		$this->original = new GP_UnitTest_Factory_For_Original;
		$this->translation_set = new GP_UnitTest_Factory_For_Translation_Set;
		$this->translation = new GP_UnitTest_Factory_For_Translation;
	}
	
	function callback( $function ) {
		return new GP_UnitTest_Generator_Callback_After_Create( $function );
	}
}

class GP_UnitTest_Factory_For_Project extends GP_UnitTest_Factory_For_Thing {
	function __construct() {
		parent::__construct( new GP_Project );
		$this->default_generation_definitions = array(
			'name' => new GP_UnitTest_Generator_Sequence( 'Project %s' ),
			'description' => 'I am a project',
			'parent_project_id' => null,
		);
	}
}

class GP_UnitTest_Factory_For_Translation_Set extends GP_UnitTest_Factory_For_Thing {
	function __construct() {
		parent::__construct( new GP_Translation_Set );
		$this->default_generation_definitions = array(
			'name' => new GP_UnitTest_Generator_Sequence( 'Translation Set %s' ),
			'slug' => 'default',
		);
	}
}

class GP_UnitTest_Factory_For_Original extends GP_UnitTest_Factory_For_Thing {
	function __construct() {
		parent::__construct( new GP_Original );
		$this->default_generation_definitions = array(
			'singular' => new GP_UnitTest_Generator_Sequence( 'Original %s' ),
		);
	}
}

class GP_UnitTest_Factory_For_Translation extends GP_UnitTest_Factory_For_Thing {
	function __construct() {
		parent::__construct( new GP_Translation );
		$this->default_generation_definitions = array(
			'translation_0' => new GP_UnitTest_Generator_Sequence( 'Translation %s' ),
		);
	}
}

class GP_UnitTest_Factory_For_Thing {
	
	var $default_generation_definitions;
	
	function __construct( $thing, $default_generation_definitions = array() ) {
		$this->default_generation_definitions = $default_generation_definitions;
		$this->thing = $thing;
	}
	
	function create( $args = array() ) {
		$generated_args = $this->generate_args( $args );
		$callbacks = array();
		$updated_fields = array();
		foreach( $generated_args as $field_name => $field_value ) {
			if ( is_object( $field_value ) && method_exists( $field_value, 'call' ) ) {
				unset( $args[$field_name] );
				$callbacks[$field_name] = $field_value;
			}
		}
		$created = $this->thing->create( $generated_args );
		if ( !$created || is_wp_error( $created ) ) return $created;
		if ( $callbacks ) {
			foreach( $callbacks as $field_name => $generator ) {
				$updated_fields[$field_name] = $generator->call( $created );
			}
			$save_result = $created->save( $updated_fields );
			if ( !$save_result || is_wp_error( $save_result ) ) return $save_result;
		}
		return $created;
	}
	
	function generate_args( $args = array(), $generation_definitions = null ) {
		if ( is_null( $generation_definitions ) ) $generation_definitions = $this->default_generation_definitions;
		foreach( $this->thing->field_names as $field_name ) {
			if ( !isset( $args[$field_name] ) ) {
				if ( !isset( $generation_definitions[$field_name] ) ) {
					continue;
				}
				$generator = $generation_definitions[$field_name];
				if ( is_string( $generator ) || is_numeric( $generator ) )
					$args[$field_name] = $generator;
				elseif ( is_object( $generator ) && method_exists( $generator, 'call' ) )
					$args[$field_name] = $generator;
				elseif ( is_object( $generator ) )
					$args[$field_name] = $generator->next();
				else
					return new WP_Error( 'invalid_argument', 'Factory default value should be either a scalar or an generator object.' );
			}
		}
		return $args;
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