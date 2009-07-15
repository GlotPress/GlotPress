<?php
/**
 * Holds common functionality for routes.
 */
class GP_Route_Main {
	function _import($file_key, $class, $block, $block_args) {
		global $gpdb;
		if ( is_uploaded_file( $_FILES[$file_key]['tmp_name'] ) ) {
			$translations = new $class();
			$result = $translations->import_from_file( $_FILES[$file_key]['tmp_name'] );
			if ( !$result ) {
				gp_notice_set( __("Couldn&#8217;t load translations from file!"), 'error' );
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
		// TODO: fix db::prepare to understand %1$s
		// now each condition has to contain a %s not to break the sequence
		$where[] = is_null( $entry->context )? '(context IS NULL OR %s IS NULL)' : 'BINARY context = %s';
		$where[] = 'BINARY singular = %s';
		$where[] = is_null( $entry->plural )? '(plural IS NULL OR %s IS NULL)' : 'BINARY plural = %s';
		$where[] = 'project_id = %d';
		$where = implode( ' AND ', $where );
		$sql = $gpdb->prepare( "SELECT * FROM $gpdb->originals WHERE $where", $entry->context, $entry->singular, $entry->plural, $project->id );
		return $gpdb->get_row( $sql );
	}
}