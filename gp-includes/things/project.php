<?php
class GP_Project extends GP_Thing {
	
	var $table_basename = 'projects';
	var $field_names = array( 'id', 'name', 'slug', 'path', 'description', 'parent_project_id' );
	var $non_updatable_attributes = array( 'id', 'path' );
	
	// Additional queries

	function by_path( $path ) {
		return $this->one( "SELECT * FROM $this->table WHERE path = '%s'", trim( $path, '/' ) );
	}
	
	function sub_projects() {
		return $this->many( "SELECT * FROM $this->table WHERE parent_project_id = %d", $this->id );
	}
	
	function top_level() {
		return $this->many( "SELECT * FROM $this->table WHERE parent_project_id IS NULL" );
	}

	// Triggers
	
	function after_save() {
		// TODO: only call it if the slug or parent project were changed
		// TODO: pass the update args to after/pre_save?
		return $this->update_path();
	}
	

	function after_create() {
		// TODO: pass some args to pre/after_create?
		// TODO: transaction? uninsert?
		if ( is_null( $this->update_path() ) ) return false;
	}


	// Field handling

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['parent_project_id'] ) && !$args['parent_project_id'] ) {
			$args['parent_project_id'] = null;
		}
		return $args;
	}

	// Helpers
	
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
			return $this->query( $query, $path, strlen($old_path) + 1, like_escape( $old_path).'%' );
		} else {
			return $res_self;
		}
	}
}
GP::$project = new GP_Project();