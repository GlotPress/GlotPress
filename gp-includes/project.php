<?php
class GP_Project {
	
	function GP_Project( $db_object ) {
		foreach((array)$db_object as $key => $value) {
			$this->$key = $value;
		}
	}
	
	function map( &$results ) {
		return array_map( create_function( '$r', 'return new GP_Project($r);' ), $results );
	}
	
	function get( &$project_or_id ) {
		global $gpdb;
		if ( is_object( $project_or_id ) )
			return $project_or_id;
		else
			return GP_Project::map( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE `id` = '%s'", $project_or_id ) ) );
	}
	
	function by_path( $path ) {
		global $gpdb;
		$path = trim( $path, '/' );
		return new GP_Project( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE path = '%s'", $path ) ) );
	}
	
	function sub_projects() {
		global $gpdb;
		return GP_Project::map( $gpdb->get_results(
			$gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE parent_project_id = %d", $this->id ) ) );
	}
	
	function top_level() {
		global $gpdb;
		return GP_Project::map( $gpdb->get_results("SELECT * FROM $gpdb->projects WHERE parent_project_id IS NULL") );
	}
}