<?php
/**
 * Routes: GP_Route_Locale class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Core class used to implement the locale route.
 *
 * @since 1.0.0
 */
class GP_Route_Locale extends GP_Route_Main {

	public function locales_get() {
		if ( NULL !== gp_get( 'all', NULL ) ) {
			$locales = GP_Locales::locales();
			usort( $locales, array( $this, 'sort_locales' ) );
		}
		else {
			$existing_locales = GP::$translation_set->existing_locales();
			$locales = array();

			foreach ( $existing_locales as $locale ) {
				$locales[] = GP_Locales::by_slug( $locale );
			}

			usort( $locales, array( $this, 'sort_locales') );
		}

		$this->tmpl( 'locales', get_defined_vars() );
	}

	public function single( $locale_slug, $current_set_slug = 'default' ) {
		$locale = GP_Locales::by_slug( $locale_slug );
		$sets = GP::$translation_set->by_locale( $locale_slug );

		usort( $sets, array( $this, 'sort_sets_by_project_id' ) );

		$projects_data = $projects = $parents = $set_slugs = $set_list = array();
		$locale_projects = wp_list_pluck( $sets, 'project_id' );

		foreach ( $sets as $set ) {
			$set_slugs[ $set->slug ] = $set;

			if ( $current_set_slug != $set->slug ) {
				continue;
			}

			// Store project data for later use
			if ( isset( $projects[ $set->project_id ] ) ) {
				$set_project = $projects[$set->project_id];
			} else {
				$set_project = GP::$project->get( $set->project_id );
				$projects[$set->project_id] = $set_project;
			}

			// We only want to list active projects
			if ( ! isset( $set_project->active ) || $set_project->active == false ) {
				continue;
			}

			$parent_id = is_null( $set_project->parent_project_id ) ? $set_project->id : $set_project->parent_project_id;

			// Store parent project data for later use
			if ( isset( $projects[$parent_id] ) ) {
				$parent_project = $projects[$parent_id];
			} else {
				$parent_project = GP::$project->get( $parent_id );
				$projects[$parent_id] = $parent_project;
			}

			// Store parent id for
			$parents[$set_project->id] = $parent_id;

			if ( ! in_array( $set_project->parent_project_id, $locale_projects ) ) {
				$projects_data[$parent_id][$set_project->id]['project'] = $set_project;
				$projects_data[$parent_id][$set_project->id]['sets'][$set->id] = $this->set_data( $set, $set_project );
				$projects_data[$parent_id][$set_project->id]['totals'] = $this->set_data( $set, $set_project );

				if ( ! isset( $projects_data[$parent_id][$set_project->id]['project'] ) ) {
					$projects_data[$parent_id][$set_project->id]['project'] = $set_project;
				}
			} else {
				while ( ! in_array( $parent_id, array_keys( $projects_data ) ) && isset( $parents[$parent_id] ) ) {
					$previous_parent = $parent_id;
					$parent_id = $parents[$parent_id];
				}

				//Orphan project - a sub project is set to active, while it's parent isn't
				if ( ! isset( $projects_data[$parent_id] ) ) {
					continue;
				}

				//For when root project has sets, and sub projects.
				if ( ! isset( $previous_parent ) || ! isset( $projects_data[$parent_id][$previous_parent] ) ) {
					$previous_parent = $parent_id;
				}

				$set_data = $projects_data[$parent_id][$previous_parent]['totals'];
				$projects_data[$parent_id][$previous_parent]['sets'][$set->id] = $this->set_data( $set, $set_project  );
				$projects_data[$parent_id][$previous_parent]['totals'] = $this->set_data( $set, $set_project, $set_data );
			}
		}

		if ( 'default' !== $current_set_slug && ! isset( $set_slugs[ $current_set_slug ] ) ) {
			return $this->die_with_404();
		}

		if ( ! empty( $set_slugs ) ) {
			// Make default the first item.
			if ( ! empty( $set_slugs[ 'default' ] ) ) {
				$default = $set_slugs[ 'default' ];
				unset( $set_slugs[ 'default' ] );
				array_unshift( $set_slugs, $default );
			}

			foreach ( $set_slugs as $set ) {
				if ( 'default' == $set->slug ) {
					if ( 'default' != $current_set_slug ) {
						$set_list[ $set->slug ] = gp_link_get( gp_url( gp_url_join( '/languages', $locale->slug ) ), __( 'Default', 'glotpress' ) );
					} else {
						$set_list[ $set->slug ] = __( 'Default', 'glotpress' );
					}
				} else {
					if ( $set->slug != $current_set_slug ) {
						$set_list[ $set->slug ] = gp_link_get( gp_url( gp_url_join( '/languages', $locale->slug, $set->slug ) ), esc_html( $set->name ) );
					} else {
						$set_list[ $set->slug ] = esc_html( $set->name );
					}
				}
			}
		}

		$can_create_locale_glossary = GP::$permission->current_user_can( 'admin' );
		$locale_glossary_translation_set = GP::$translation_set->by_project_id_slug_and_locale( 0, $current_set_slug, $locale_slug );
		$locale_glossary = GP::$glossary->by_set_id( $locale_glossary_translation_set->id );

		$this->tmpl( 'locale', get_defined_vars() );
	}

	private function set_data( $set, $project, $set_data = null ) {
		if ( ! $set_data ) {
			$set_data = new stdClass;

			$set_data->slug = $set->slug;
			$set_data->project_path = $project->path;
			$set_data->waiting_count = $set->waiting_count();
			$set_data->current_count = $set->current_count();
			$set_data->fuzzy_count   = $set->fuzzy_count();
			$set_data->all_count     = $set->all_count();
		}
		else {
			$set_data->waiting_count += $set->waiting_count();
			$set_data->current_count += $set->current_count();
			$set_data->fuzzy_count   += $set->fuzzy_count();
			$set_data->all_count     += $set->all_count();
		}

		if ( ! isset( $set_data->name ) ) {
			$set_data->name = $project->name;
		}

		return $set_data;
	}

	private function sort_locales( $a, $b ) {
		return $a->english_name > $b->english_name;
	}

	private function sort_sets_by_project_id( $a, $b ) {
		return $a->project_id > $b->project_id;
	}
}
