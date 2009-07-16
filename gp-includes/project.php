<?php
class GP_Project {
	
	function GP_Project( $db_object ) {
		$this->_merge( $db_object );
	}
	
	function _merge( $db_object ) {
		foreach((array)$db_object as $key => $value) {
			$this->$key = $value;
		}
	}
	
	/**
	 * @static
	 */
	function map( &$results ) {
		return array_map( create_function( '$r', 'return GP_Project::coerce($r);' ), $results );
	}
	
	function coerce( $project ) {
		if ( is_wp_error( $project ) || !$project )
			return false;
		else
			return new GP_Project( $project );
	}
	
	/**
	 * @static
	 */
	function create( $args ) {
		global $gpdb;
		$res = $gpdb->insert( $gpdb->projects, $args );
		if ( is_null( $res ) ) return $res;
		$inserted = new GP_Project( $args );
		$inserted->id = $gpdb->insert_id;
		// TODO: transaction? uninsert?
		if ( is_null( $inserted->update_path() ) ) return null;
		return $inserted;
	}
	
	function create_and_select() {
		
	}
	
	/**
	 * @static
	 */
	function get( &$project_or_id ) {
		global $gpdb;
		if ( is_object( $project_or_id ) ) $project_or_id = $project_or_id->id;
		return GP_Project::coerce( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE `id` = '%s'", $project_or_id ) ) );
	}
	
	/**
	 * @static
	 */
	function by_path( $path ) {
		global $gpdb;
		$path = trim( $path, '/' );
		return new GP_Project( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE path = '%s'", $path ) ) );
	}
	
	function sub_projects() {
		global $gpdb;
		return GP_Project::map( $gpdb->get_results(
			$gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE parent_project_id = %d", $this->id ) ) );
	}
	
	/**
	 * @static
	 */
	function top_level() {
		global $gpdb;
		return GP_Project::map( $gpdb->get_results("SELECT * FROM $gpdb->projects WHERE parent_project_id IS NULL") );
	}
	
	/**
	 * @static
	 */
	function all() {
		global $gpdb;
		return GP_Project::map( $gpdb->get_results("SELECT * FROM $gpdb->projects") );
	}

	/**
	 * @static
	 */
	function get_results() {
		global $gpdb;
		return GP_Project::map( $gpdb->get_results( $query ) );
	}
	
	/**
	 * @static
	 */
	function _map_args( $args ) {
		if ( isset( $args['parent_project_id'] ) && !$args['parent_project_id'] ) {
			$args['parent_project_id'] = null;
		}
		unset( $args['id'] );
		unset( $args['path'] );
		return $args;
	}
	
	function save( $args = false ) {
		global $gpdb;
		if ( !$args ) $args = get_object_vars( $this );
		if ( !is_array( $args ) ) $args = (array)$args;
		$update_res  = $gpdb->update( $gpdb->projects, self::_map_args( $args ), array( 'id' => $this->id ) );
		$this->_merge( $args );
		if ( is_null( $update_res ) ) return $update_res;
		if ( isset( $args['slug'] ) || isset( $args['parent_project_id'] ) ) {
			return $this->update_path();
		}
		return $update_res;
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
		$query = "UPDATE $gpdb->projects SET path = CONCAT(%s, SUBSTRING(path, %d)) WHERE path LIKE %s";
		return $gpdb->query( $gpdb->prepare( $query, $path, strlen($old_path) + 1, like_escape( $old_path).'%' ) );
	}
	
	function reload() {
		$this->_merge( self::get( $this->id ) );
	}
}