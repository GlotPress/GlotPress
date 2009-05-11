<?php
function gp_route_index( ) {
	global $gpdb;
	$title = 'Welcome to GlotPress';
	$projects = $gpdb->get_results("SELECT * FROM $gpdb->projects WHERE parent_project_id IS NULL");
	gp_tmpl_load( 'home', get_defined_vars() );
}