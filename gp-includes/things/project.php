<?php
class GP_Project extends GP_Thing {
	
	var $field_names = array( 'id', 'name', 'slug', 'path', 'description', 'parent_project_id' );
	var $non_updatable_attributes = array( 'id', 'path' );
	var $errors = array();
	var $table;
	
	function __construct( $db_object = array() ) {
		global $gpdb;
		$this->table = $gpdb->projects;
		if ( $db_object) {
			$this->init( $db_object );
		}
	}
	
	/**
	 * Normalizes an array with key-value pairs representing
	 * a GP_Project object.
	 */
	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['parent_project_id'] ) && !$args['parent_project_id'] ) {
			$args['parent_project_id'] = null;
		}
		return $args;
	}


	function after_create() {
		// TODO: pass some args to pre/after_create?
		// TODO: transaction? uninsert?
		if ( is_null( $this->update_path() ) ) return false;
	}
	
	function after_save() {
		// TODO: pass the update args to after/pre_save?
		//if ( isset( $args['slug'] ) || isset( $args['parent_project_id'] ) ) {
			return $this->update_path();
		//}		
	}
	
	function by_path( $path ) {
		global $gpdb;
		return $this->coerce( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE path = '%s'", trim( $path, '/' ) ) ) );
	}
	
	/**
	 * Get all sub-projects of this project.
	 */
	function sub_projects() {
		global $gpdb;
		return $this->map( $gpdb->get_results(
			$gpdb->prepare( "SELECT * FROM $this->table WHERE parent_project_id = %d", $this->id ) ) );
	}
	
	/**
	 * @static
	 */
	function top_level() {
		global $gpdb;
		return $this->map( $gpdb->get_results("SELECT * FROM $gpdb->projects WHERE parent_project_id IS NULL") );
	}
	
	/**
	 * @static
	 */
	function all() {
		global $gpdb;
		return $this->map( $gpdb->get_results("SELECT * FROM $gpdb->projects") );
	}
	
	/**
	 * Updates this project's and its chidlren's paths, according to its current slug.
	 */
	function update_path() {
		global $gpdb;
		$old_path = isset( $this->path )? $this->path : '';
		$parent_project = $this->get( $this->parent_project_id );
		if ( $parent_project )
			$path = gp_url_join( $parent_project->path, $this->slug );
		elseif ( !$gpdb->last_error )
			$path = $this->slug;
		else
			return null;
		$res_self = $gpdb->update( $gpdb->projects, array( 'path' => $path ), array( 'id' => $this->id ) );
		if ( is_null( $res_self ) ) return $res_self;
		// update children's paths, too
		if ( $old_path ) {
			$query = "UPDATE $gpdb->projects SET path = CONCAT(%s, SUBSTRING(path, %d)) WHERE path LIKE %s";
			return $gpdb->query( $gpdb->prepare( $query, $path, strlen($old_path) + 1, like_escape( $old_path).'%' ) );
		} else {
			return $res_self;
		}
	}
}
GP::$project = new GP_Project();