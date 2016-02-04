<?php
class GP_Project extends GP_Thing {

	var $table_basename = 'gp_projects';
	var $field_names = array( 'id', 'name', 'slug', 'path', 'description', 'parent_project_id', 'source_url_template', 'active' );
	var $int_fields = array( 'id', 'parent_project_id', 'active' );
	var $non_updatable_attributes = array( 'id' );

	public $id;
	public $name;
	public $slug;
	public $path;
	public $description;
	public $parent_project_id;
	public $source_url_template;
	public $active;

	public function restrict_fields( $project ) {
		$project->name_should_not_be('empty');
		$project->slug_should_not_be('empty');
	}

	// Additional queries

	public function by_path( $path ) {
		return $this->one( "SELECT * FROM $this->table WHERE path = %s", trim( $path, '/' ) );
	}

	public function sub_projects() {
		$sub_projects = $this->many( "SELECT * FROM $this->table WHERE parent_project_id = %d ORDER BY active DESC, id ASC", $this->id );
		$sub_projects = apply_filters( 'gp_projects', $sub_projects, $this->id );

		return $sub_projects;
	}

	public function top_level() {
		$projects = $this->many( "SELECT * FROM $this->table WHERE parent_project_id IS NULL OR parent_project_id < 1 ORDER BY name ASC" );
		$projects = apply_filters( 'gp_projects', $projects, 0 );

		return $projects;
	}

	// Triggers

	public function after_save() {
		do_action( 'gp_project_saved', $this );
		// TODO: pass the update args to after/pre_save?
		// TODO: only call it if the slug or parent project were changed
		return !is_null( $this->update_path() );
	}

	public function after_create() {
		do_action( 'gp_project_created', $this );
		// TODO: pass some args to pre/after_create?
		if ( is_null( $this->update_path() ) ) return false;
	}

	// Field handling

	public function normalize_fields( $args ) {
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
			if ( 'on' === $args['active'] ) $args['active'] = 1;
			if ( !$args['active'] ) $args['active'] = 0;
		}
		return $args;
	}

	// Helpers

	/**
	 * Updates this project's and its chidlren's paths, according to its current slug.
	 */
	public function update_path() {
		global $wpdb;
		$old_path = isset( $this->path )? $this->path : '';
		$parent_project = $this->get( $this->parent_project_id );
		if ( $parent_project )
			$path = gp_url_join( $parent_project->path, $this->slug );
		elseif ( !$wpdb->last_error )
			$path = $this->slug;
		else
			return null;
		$this->path = $path;
		$res_self = $this->update( array( 'path' => $path ) );
		if ( is_null( $res_self ) ) return $res_self;
		// update children's paths, too
		if ( $old_path ) {
			$query = "UPDATE $this->table SET path = CONCAT(%s, SUBSTRING(path, %d)) WHERE path LIKE %s";
			return $this->query( $query, $path, strlen($old_path) + 1, $wpdb->esc_like( $old_path).'%' );
		} else {
			return $res_self;
		}
	}

	/**
	 * Regenrate the paths of all projects from its parents slugs
	 */
	public function regenerate_paths( $parent_project_id = null ) {
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

	public function source_url( $file, $line ) {
		if ( $this->source_url_template() ) {
			return str_replace( array('%file%', '%line%'), array($file, $line), $this->source_url_template() );
		}
		return false;
	}

	public function source_url_template() {
		if ( isset( $this->user_source_url_template ) )
			return $this->user_source_url_template;
		else {
			if ( $this->id && is_user_logged_in() && ( $templates = get_user_meta( get_current_user_id(), 'gp_source_url_templates', true ) )
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
	public function path_to_root() {
		$path = array();
		if ( $this->parent_project_id ) {
			$parent_project = $this->get( $this->parent_project_id );

			if ( $parent_project ) {
				$path = $parent_project->path_to_root();
			}
		}
		return array_merge( array( &$this ), $path );
	}

	public function set_difference_from( $other_project ) {
		$this_sets  = (array) GP::$translation_set->by_project_id( $this->id );
		$other_sets = (array) GP::$translation_set->by_project_id( $other_project->id );
		$added      = array();
		$removed    = array();

		foreach ( $other_sets as $other_set ) {
			$found = gp_array_any( function( $set ) use ( $other_set ) {
				return ( $set->locale == $other_set->locale && $set->slug = $other_set->slug );
			}, $this_sets );

			if ( ! $found ) {
				$added[] = $other_set;
			}
		}

		foreach ( $this_sets as $this_set ) {
			$found = gp_array_any( function( $set ) use ( $this_set ) {
				return ( $set->locale == $this_set->locale && $set->slug = $this_set->slug );
			}, $other_sets );

			if ( ! $found ) {
				$removed[] = $this_set;
			}
		}

		return array(
			'added' => $added,
			'removed' => $removed
		);
	}

	public function copy_sets_and_translations_from( $source_project_id ) {
		$sets = GP::$translation_set->by_project_id( $source_project_id );

		foreach( $sets as $to_add ) {
			$new_set = GP::$translation_set->create( array( 'project_id' => $this->id, 'name' => $to_add->name, 'locale' => $to_add->locale, 'slug' => $to_add->slug ) );
			if ( ! $new_set  ) {
				$this->errors[] = sprintf( __( 'Couldn&#8217;t add translation set named %s', 'glotpress' ), esc_html( $to_add->name ) );
			} else {
				//Duplicate translations
				$new_set->copy_translations_from( $to_add->id );
			}
		}
	}

	public function copy_originals_from( $source_project_id ) {
		global $wpdb;
		return $this->query("
			INSERT INTO $wpdb->gp_originals (
				`project_id`, `context`, `singular`, `plural`, `references`, `comment`, `status`, `priority`, `date_added`
			)
			SELECT
				%s AS `project_id`, `context`, `singular`, `plural`, `references`, `comment`, `status`, `priority`, `date_added`
			FROM $wpdb->gp_originals WHERE project_id = %s", $this->id, $source_project_id
		);
	}

	/**
	 * Gives an array of project objects starting from the current project children
	 * then its grand children etc
	 *
	 * @return array
	 */
	public function inclusive_sub_projects() {
		$sub_projects = $this->sub_projects();
		foreach ( $sub_projects as $sub ) {
			$sub_projects = array_merge( $sub_projects, $sub->inclusive_sub_projects() );
		}

		return $sub_projects;
	}

	public function duplicate_project_contents_from( $source_project ){
		$source_sub_projects = $source_project->inclusive_sub_projects();

		//Duplicate originals, translations sets and translations for the root project
		$this->copy_originals_from( $source_project->id ) ;
		$this->copy_sets_and_translations_from( $source_project->id );

		//Keep a list of parents to preserve hierarchy
		$parents = array();
		$parents[$source_project->id] = $this->id;

		//Duplicate originals, translations sets and translations for the child projects
		foreach ( $source_sub_projects as $sub ) {
			$copy_project = new GP_Project( $sub->fields() );
			$copy_project->parent_project_id = $parents[$sub->parent_project_id];
			$parent_project = $copy_project->get( $copy_project->parent_project_id );

			$copy_project->path = gp_url_join( $parent_project->path, $copy_project->slug );
			$copy = GP::$project->create( $copy_project );
			$parents[$sub->id] = $copy->id;

			$copy->copy_originals_from( $sub->id );
			$copy->copy_sets_and_translations_from( $sub->id );
		}
	}

}
GP::$project = new GP_Project();
