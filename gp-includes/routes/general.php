<?php
function gp_route_index( ) {
	global $gpdb;
	$title = __('Welcome to GlotPress');
	$projects = GP::$project->top_level();
	gp_tmpl_load( 'home', get_defined_vars() );
}