<?php
/**
 * Holds common functionality for routes.
 */
class GP_Route_Main extends GP_Route {
	function _import( $file_key, $class, $block, $block_args ) {
		global $gpdb;
		if ( is_uploaded_file( $_FILES[$file_key]['tmp_name'] ) ) {
			$translations = new $class();
			$result = $translations->import_from_file( $_FILES[$file_key]['tmp_name'] );
			if ( !$result ) {
				$this->errors[] = __("Couldn&#8217;t load translations from file!");
			} else {
				$block_args[] = $translations;
				call_user_func_array( $block, $block_args );
			}
			return true;
		}
		return false;
	}

	function _find_original( $project, $entry ) {
		global $gpdb;
		$where = array();
		// now each condition has to contain a %s not to break the sequence
		$where[] = is_null( $entry->context )? '(context IS NULL OR %s IS NULL)' : 'BINARY context = %s';
		$where[] = 'BINARY singular = %s';
		$where[] = is_null( $entry->plural )? '(plural IS NULL OR %s IS NULL)' : 'BINARY plural = %s';
		$where[] = 'project_id = %d';
		$where = implode( ' AND ', $where );
		return GP::$original->one( "SELECT * FROM $gpdb->originals WHERE $where", $entry->context, $entry->singular, $entry->plural, $project->id );
	}

	// TODO: move these as a template helper
	
	function _options_from_projects( $projects ) {
		// TODO: mark which nodes are editable by the current user
		$tree = array();
		$top = array();
		foreach( $projects as $p ) {
			$tree[$p->id]['self'] = $p;
			if ( $p->parent_project_id ) {
				$tree[$p->parent_project_id]['children'][] = $p->id;
			} else {
				$top[] = $p->id;
			}
		}
		$options = array( '' => __('No parent') );
		$stack = array();
		foreach( $top as $top_id ) {
			$stack = array( $top_id );
			while ( !empty( $stack ) ) {
				$id = array_pop( $stack );
				$tree[$id]['level'] = gp_array_get( $tree[$id], 'level', 0 );
				$options[$id] = str_repeat( '-', $tree[$id]['level'] ) . $tree[$id]['self']->name;
				foreach( gp_array_get( $tree[$id], 'children', array() ) as $child_id ) {
					$stack[] = $child_id;
					$tree[$child_id]['level'] = $tree[$id]['level'] + 1;
				}
			}
		}
		return $options;
	}

	function _options_from_locales( $locales ) {
		$values = array_map( create_function( '$l', 'return $l->slug;'), $locales );
		$labels = array_map( create_function( '$l', 'return $l->slug." - ". $l->english_name;'), $locales );
		sort( $values );
		sort( $labels );
		return array_combine($values, $labels);
	}
}
