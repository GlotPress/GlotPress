<?php
/**
 * Things: GP_Project class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 1.0.0
 */

/**
 * Core class used to implement the projects.
 *
 * @since 1.0.0
 */
class GP_Project extends GP_Thing {

	/**
	 * Name of the database table.
	 *
	 * @var string $table_basename
	 */
	var $table_basename = 'gp_projects';

	/**
	 * List of field names for a translation.
	 *
	 * @var array $field_names
	 */
	var $field_names = array( 'id', 'name', 'slug', 'path', 'description', 'parent_project_id', 'source_url_template', 'active', 'plurals_type' );

	/**
	 * List of field names which have an integer value.
	 *
	 * @var array $int_fields
	 */
	var $int_fields = array( 'id', 'parent_project_id', 'active' );

	/**
	 * List of field names which cannot be updated.
	 *
	 * @var array $non_updatable_attributes
	 */
	var $non_updatable_attributes = array( 'id' );

	/**
	 * ID of the project.
	 *
	 * @var int $id
	 */
	public $id;

	/**
	 * Name of the project.
	 *
	 * @var string $name
	 */
	public $name;

	/**
	 * Slug of the project.
	 *
	 * @var string $slug
	 */
	public $slug;

	/**
	 * Path of the project.
	 *
	 * @var string $path
	 */
	public $path;

	/**
	 * Description of the project.
	 *
	 * @var string $description
	 */
	public $description;

	/**
	 * Parent id of the project.
	 *
	 * @var string $parent_project_id
	 */
	public $parent_project_id;

	/**
	 * URL of the source template of the project.
	 *
	 * @var string $source_url_template
	 */
	public $source_url_template;

	/**
	 * Active state of the project.
	 *
	 * @var int $active
	 */
	public $active;

	/**
	 * URL of the user source template of the project.
	 *
	 * @var string $user_source_url_template
	 */
	public $user_source_url_template;

	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		$rules->name_should_not_be( 'empty' );
		$rules->slug_should_not_be( 'empty' );
	}

	// Additional queries

	public function by_path( $path ) {
		/**
		 * Filters the prefix for the locale glossary path.
		 *
		 * @since 2.3.1
		 *
		 * @param string $$locale_glossary_path_prefix Prefix for the locale glossary path.
		 */
		$locale_glossary_path_prefix = apply_filters( 'gp_locale_glossary_path_prefix', '/languages' );

		if ( $locale_glossary_path_prefix === $path ) {
			return GP::$glossary->get_locale_glossary_project();
		}
		return $this->one( "SELECT * FROM $this->table WHERE path = %s", trim( $path, '/' ) );
	}

	/**
	 * Fetches the project by id or object.
	 *
	 * @since 2.3.0
	 *
	 * @param int|object $thing_or_id A project or the id.
	 * @return GP_Project The project
	 */
	public function get( $thing_or_id ) {
		if ( is_numeric( $thing_or_id ) && 0 === (int) $thing_or_id ) {
			return GP::$glossary->get_locale_glossary_project();
		}

		return parent::get( $thing_or_id );
	}

	/**
	 * Retrieves the sub projects
	 *
	 * @return array Array of GP_Project
	 */
	public function sub_projects() {
		$sub_projects = $this->many( "SELECT * FROM $this->table WHERE parent_project_id = %d ORDER BY active DESC, id ASC", $this->id );

		/**
		 * Filter the list of sub-projects of a project.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $sub_projects An array of sub projects as GP_Project.
		 * @param string $project_id   ID of the current project. Can be zero at the top level.
		 */
		$sub_projects = apply_filters( 'gp_projects', $sub_projects, $this->id );

		return $sub_projects;
	}

	public function top_level() {
		$projects = $this->many( "SELECT * FROM $this->table WHERE parent_project_id IS NULL OR parent_project_id < 1 ORDER BY name ASC" );

		/** This filter is documented in gp-includes/things/project.php */
		$projects = apply_filters( 'gp_projects', $projects, 0 );

		return $projects;
	}

	// Triggers

	/**
	 * Executes after creating a project.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function after_create() {
		/**
		 * Fires after creating a project.
		 *
		 * @since 1.0.0
		 *
		 * @param GP_Project $project The project that was created.
		 */
		do_action( 'gp_project_created', $this );

		// TODO: pass some args to pre/after_create?
		if ( is_null( $this->update_path() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Executes after saving a project.
	 *
	 * @since 1.0.0
	 * @since 2.4.0 Added the `$project_before` parameter.
	 *
	 * @param GP_Project $project_before Project before the update.
	 * @return bool
	 */
	public function after_save( $project_before ) {
		/**
		 * Fires after saving a project.
		 *
		 * @since 1.0.0
		 * @since 2.4.0 Added the `$project_before` parameter.
		 *
		 * @param GP_Project $project        Project following the update.
		 * @param GP_Project $project_before Project before the update.
		 */
		do_action( 'gp_project_saved', $this, $project_before );

		// TODO: pass the update args to after/pre_save?
		// TODO: only call it if the slug or parent project were changed
		return ! is_null( $this->update_path() );
	}

	/**
	 * Executes after deleting a project.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function after_delete() {
		/**
		 * Fires after deleting a project.
		 *
		 * @since 2.0.0
		 *
		 * @param GP_Project $project The project that was deleted.
		 */
		do_action( 'gp_project_deleted', $this );

		return true;
	}

	/**
	 * Normalizes an array with key-value pairs representing
	 * a GP_Project object.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments for a GP_Project object.
	 * @return array Normalized arguments for a GP_Project object.
	 */
	public function normalize_fields( $args ) {
		$args = (array) $args;

		if ( isset( $args['parent_project_id'] ) ) {
			$args['parent_project_id'] = $this->force_false_to_null( $args['parent_project_id'] );
		}

		if ( isset( $args['slug'] ) && !$args['slug'] ) {
			$args['slug'] = $args['name'];
		}

		if ( ! empty( $args['slug'] ) ) {
			$args['slug'] = gp_sanitize_slug( $args['slug'] );
		}

		if ( ( isset( $args['path']) && !$args['path'] ) || !isset( $args['path'] ) || is_null( $args['path'] )) {
			unset( $args['path'] );
		}

		if ( isset( $args['active'] ) ) {
			if ( 'on' === $args['active'] ) {
				$args['active'] = 1;
			}

			if ( !$args['active'] ) {
				$args['active'] = 0;
			}
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

		$path = trim( $path, '/' );

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
			$project->update( array( 'path' => trim( gp_url_join( $path, $project->slug ), '/' ) ) );
			$this->regenerate_paths( $project->id );
		}
	}

	public function source_url( $file, $line ) {
		$source_url = false;
		if ( $source_url_template = $this->source_url_template() ) {
			$source_url = str_replace( array( '%file%', '%line%' ), array( $file, $line ), $source_url_template );
		}

		/**
		 * Allows per-reference overriding of the source URL defined as project setting.
		 *
		 * @since 2.2.0
		 *
		 * @param string|false $source_url The originally generated source URL, or false if no URL is available.
		 * @param GP_Project $project The current project.
		 * @param string $file The referenced file name.
		 * @param string $line The line number in the referenced file.
		 */
		return apply_filters( 'gp_reference_source_url', $source_url, $this, $file, $line );
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
			if ( ! gp_array_any( array( $this, '_compare_set_item' ), $this_sets, $other_set ) ) {
				$added[] = $other_set;
			}
		}

		foreach ( $this_sets as $this_set ) {
			if ( ! gp_array_any( array( $this, '_compare_set_item' ), $other_sets, $this_set ) ) {
				$removed[] = $this_set;
			}
		}

		return array(
			'added' => $added,
			'removed' => $removed
		);
	}

	public function _compare_set_item( $set, $this_set ) {
		return ( $set->locale == $this_set->locale && $set->slug = $this_set->slug );
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

	/**
	 * Deletes a project and all of sub projects, translations, translation sets, originals and glossaries.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function delete() {
		GP::$project->delete_many( array( 'parent_project_id' => $this->id ) );

		GP::$translation_set->delete_many( array( 'project_id' => $this->id ) );

		GP::$original->delete_many( array( 'project_id' => $this->id ) );

		return parent::delete();
	}
}
GP::$project = new GP_Project();
