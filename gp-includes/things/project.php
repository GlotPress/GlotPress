<?php
class GP_Project extends GP_Thing {
	
	var $table_basename = 'projects';
	var $field_names = array( 'id', 'name', 'slug', 'path', 'description', 'parent_project_id', 'source_url_template' );
	var $non_updatable_attributes = array( 'id', 'path' );


	function restrict_fields( $project ) {
		$project->name_should_not_be('empty');
		$project->slug_should_not_be('empty');
	}
	
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
		// TODO: pass the update args to after/pre_save?		
		// TODO: only call it if the slug or parent project were changed
		return !is_null( $this->update_path() );
	}
	

	function after_create() {
		// TODO: pass some args to pre/after_create?
		if ( is_null( $this->update_path() ) ) return false;
		if ( !$this->copy_permissions_from_parent() ) return false;
	}


	// Field handling

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['parent_project_id'] ) ) {
			$args['parent_project_id'] = $this->force_false_to_null( $args['parent_project_id'] );
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
		$res_self = $this->update( array( 'path' => $path ) );
		if ( is_null( $res_self ) ) return $res_self;
		// update children's paths, too
		if ( $old_path ) {
			$query = "UPDATE $this->table SET path = CONCAT(%s, SUBSTRING(path, %d)) WHERE path LIKE %s";
			return $this->query( $query, $path, strlen($old_path) + 1, like_escape( $old_path).'%' );
		} else {
			return $res_self;
		}
	}
	
	function copy_permissions_from_parent() {
		if ( !$this->parent_project_id ) return true;
		$permissions = GP::$permission->find( array( 'action' => 'write', 'object_type' => 'project',  'object_id' => $this->parent_project_id )  );
		if ( !is_array( $permissions ) ) return false;
		foreach( $permissions as $permission ) {
			if ( !GP::$permission->create( array( 'user_id' => $permission->user_id, 'action' => 'write',
					'object_type' => 'project',  'object_id' => $this->id ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Regenrate the paths of all projects from its parents slugs
	 */
	function regenerate_paths( $parent_project_id = null ) {
		// TODO: do it with one query. Use the tree generation code from GP_Route_Main::_options_from_projects()
		if ( $parent_project_id ) {
			$parent_project = $this->get( $parent_project_id );
			$path = $parent_project->path;
		} else {
			$path = '';
			$parent_project_id = null;
		}
		$projects = $this->find( array( 'parent_project_id' => $parent_project_id ) );
		foreach( (array)$projects as $project ) {
			$project->update( array( 'path' => gp_url_join( $path, $project->slug ) ) );
			$this->regenerate_paths( $project->id );
		}
	}
	
	function source_url( $file, $line ) {
		if ( $this->source_url_template ) {
			return str_replace( array('%file%', '%line%'), array($file, $line), $this->source_url_template );
		}
		return false;
	}
}
GP::$project = new GP_Project();