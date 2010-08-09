<?php
class GP_Project extends GP_Thing {
	
	var $table_basename = 'projects';
	var $field_names = array( 'id', 'name', 'slug', 'path', 'description', 'parent_project_id', 'source_url_template', 'active' );
	var $non_updatable_attributes = array( 'id' );


	function restrict_fields( $project ) {
		$project->name_should_not_be('empty');
	}
	
	// Additional queries

	function by_path( $path ) {
		return $this->one( "SELECT * FROM $this->table WHERE path = '%s'", trim( $path, '/' ) );
	}
	
	function sub_projects() {
		return $this->many( "SELECT * FROM $this->table WHERE parent_project_id = %d ORDER BY active DESC, id ASC", $this->id );
	}
	
	function top_level() {
		return $this->many( "SELECT * FROM $this->table WHERE parent_project_id IS NULL ORDER BY name ASC" );
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
	}

	// Field handling

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['parent_project_id'] ) ) {
			$args['parent_project_id'] = $this->force_false_to_null( $args['parent_project_id'] );
		}
		if ( isset( $args['slug'] ) && !$args['slug'] ) {
			$args['slug'] = gp_sanitize_for_url( $args['name'] );
		}
		if ( ( isset( $args['path']) && !$args['path'] ) || !isset( $args['path'] ) || is_null( $args['path'] )) {
			unset( $args['path'] );
		}
		if ( isset( $args['active'] ) ) {
			if ( 'on' == $args['active'] ) $args['active'] = 1;
			if ( !$args['active'] ) $args['active'] = 0;
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
		$this->path = $path;
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
		if ( $this->source_url_template() ) {
			return str_replace( array('%file%', '%line%'), array($file, $line), $this->source_url_template() );
		}
		return false;
	}
	
	function source_url_template() {
		if ( isset( $this->user_source_url_template ) )
			return $this->user_source_url_template;
		else {
			if ( $this->id && GP::$user->logged_in() && ($templates = GP::$user->current()->get_meta( 'source_url_templates' ))
					 && isset( $templates[$this->id] ) ) {
				$this->user_source_url_template = $templates[$this->id];
				return $this->user_source_url_template;
			} else {
				return $this->source_url_template;
			}
		}
	}
	
	/**
	 * Gives an array of project objects starting from the current project
	 * then its parent, its parent and up to the root
	 * 
	 * @todo Cache the results. Invalidation is tricky, because on each project update we need to invalidate the cache
	 * for all of its children.
	 * 
	 * @return array
	 */
	function path_to_root() {
		$path = array();
		if ( $this->parent_project_id ) {
			$parent_project = $this->get( $this->parent_project_id );
			$path = $parent_project->path_to_root();
		}
		return array_merge( array( &$this ), $path );
	}
	
	function set_difference_from( $other_project ) {
		$this_sets = (array)GP::$translation_set->by_project_id( $this->id );
		$other_sets = (array)GP::$translation_set->by_project_id( $other_project->id );
		$added = array();
		$removed = array();
		foreach( $other_sets as $other_set ) {
			$vars = array( 'locale' => $other_set->locale, 'slug' => $other_set->slug );
			if ( !gp_array_any( lambda('$set', '$set->locale == $locale && $set->slug == $slug', $vars ), $this_sets ) ) {
				$added[] = $other_set;
			}
		}
		foreach( $this_sets as $this_set ) {
			$vars = array( 'locale' => $this_set->locale, 'slug' => $this_set->slug );
			if ( !gp_array_any( lambda('$set', '$set->locale == $locale && $set->slug == $slug', $vars ), $other_sets ) ) {
				$removed[] = $this_set;
			}
		}
		return compact( 'added', 'removed' );
	}	
}
GP::$project = new GP_Project();