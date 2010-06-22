<?php
class GP_UnitTest_Factory {
	function __construct() {
		$this->project = new GP_UnitTest_Factory_For_Project;
	}
}

class GP_UnitTest_Factory_For_Project extends GP_UnitTest_Factory_For_Thing {
	function __construct() {
		parent::__construct( 'GP_Project', array(
			'name' => new GP_UnitTest_Generator_Sequence( 'Project %s' ),
			'description' => 'I am a project',
			'parent_project_id' => null,
		) );
	}
}

class GP_UnitTest_Factory_For_Thing {
	
	var $argument_generation_defintions;
	
	function __construct( $thing, $argument_generation_defintions ) {
		$this->argument_generation_defintions = $argument_generation_defintions;
		$this->thing = $thing;
	}
	
	function create( $args = array() ) {
		$generated_args = $this->generate_args( $args );
		return $this->thing->create( $generated_args );
	}
	
	function generate_args( $args = array() ) {
		foreach( $this->thing->field_names as $field_name ) {
			if ( !isset( $args[$field_name] ) ) {
				if ( !isset( $this->argument_generation_defintions[$field_name] ) ) {
					continue;
				}
				$generator = $this->argument_generation_defintions[$field_name];
				if ( is_string( $generator ) || is_numeric( $generator ) )
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