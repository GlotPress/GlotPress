<?php
class GP_Project extends GP_Thing {
	
	var $field_names = array( 'id', 'name', 'slug', 'path', 'description', 'parent_project_id' );
	var $non_updatable_attributes = array( 'id', 'array' );
	var $errors = array();
	var $table;
	
	function GP_Project( $db_object = array() ) {
		global $gpdb;
		$this->table = $gpdb->projects;
		if ( $db_object) {
			$this->_init( $db_object );
		}
	}
	
	/**
	 * Normalizes an array with key-values pairs representing
	 * a GP_Project object.
	 */
	function normalize_values( $args ) {
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
	
	/**
	 * @static
	 */
	function by_path( $path ) {
		global $gpdb;
		return self::$i->_coerce( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE path = '%s'", trim( $path, '/' ) ) ) );
	}
	
	/**
	 * Get all sub-projects of this project.
	 */
	function sub_projects() {
		global $gpdb;
		return self::map( $gpdb->get_results(
			$gpdb->prepare( "SELECT * FROM $this->table WHERE parent_project_id = %d", $this->id ) ) );
	}
	
	/**
	 * @static
	 */
	function top_level() {
		global $gpdb;
		return self::map( $gpdb->get_results("SELECT * FROM $gpdb->projects WHERE parent_project_id IS NULL") );
	}
	
	/**
	 * @static
	 */
	function all() {
		global $gpdb;
		return self::map( $gpdb->get_results("SELECT * FROM $gpdb->projects") );
	}
	
	/**
	 * Updates this project's and its chidlren's paths, according to its current slug.
	 */
	function update_path() {
		global $gpdb;
		$old_path = isset( $this->path )? $this->path : '';
		$parent_project = self::get( $this->parent_project_id );
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
	
	/*
	Static methods, which need late binding -- just proxy them through the singleton instance in self::$i
	TODO TODO: think of a way not to copy/paste them. It's as lame as growing potatoes for your chicken soup.
	*/
	function map() {
		$args = func_get_args();
		return call_user_func_array( array( self::$i, '_'.__FUNCTION__ ), $args );
	}
	
	function coerce( $project ) {
		$args = func_get_args();
		return call_user_func_array( array( self::$i, '_'.__FUNCTION__ ), $args );
	}
	
	function create() {
		$args = func_get_args();
		return call_user_func_array( array( self::$i, '_'.__FUNCTION__ ), $args );
	}
	
	function create_and_select() {
		$args = func_get_args();
		return call_user_func_array( array( self::$i, '_'.__FUNCTION__ ), $args );
	}
	
	function get( $project_or_id ) {
		$args = func_get_args();
		return call_user_func_array( array( self::$i, '_'.__FUNCTION__ ), $args );
	}
	
}
GP_Project::$i = new GP_Project();