<?php
class GP_Project {
	
	function get( &$project_or_id ) {
		global $gpdb;
		if ( is_object( $project_or_id ) )
			return $project_or_id;
		else
			return $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE `id` = '%s'", $project_or_id ) );
	}
	
	function by_path( $path ) {
		global $gpdb;
		$path = trim( $path, '/' );
		return $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE path = '%s'", $path ) );
	}
}