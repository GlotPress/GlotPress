<?php
function gp_route_project( $path ) {
	global $gpdb;
	$project = $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE path = '%s'", $path ) );
	if ( !$project )
		gp_tmpl_404();
	gp_tmpl_page( 'project', get_defined_vars() );
}