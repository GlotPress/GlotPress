<?php
class GP_Route_Locale extends GP_Route_Main {

	function locales_get() {
		$locales = GP_Locales::locales();

		$this->tmpl( 'locales', get_defined_vars() );
	}

	function single( $locale_slug ) {
		$locale = GP_Locales::by_slug( $locale_slug );
		$sets = GP::$translation_set->by_locale( $locale_slug );

		//TODO: switch to wp_list_pluck
		$locale_projects = $projects_data = $projects = $parents = array();
		foreach ( $sets as $key => $value ) {
			$locale_projects[ $key ] = $value->project_id;
		}

		foreach ( $sets as $set ) {
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

}