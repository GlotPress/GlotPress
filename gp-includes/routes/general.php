<?php
function gp_route_index( ) {
	global $gpdb;
	$title = 'Welcome to GlotPress';
	$projects = $gpdb->get_results("SELECT * FROM $gpdb->projects");
	gp_tmpl_page( 'home', get_defined_vars() );
}