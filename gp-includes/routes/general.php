<?php
function gp_route_index( ) {
	global $gpdb;
	$title = __('Welcome to GlotPress');
	$projects = GP_Project::top_level();
	gp_tmpl_load( 'home', get_defined_vars() );
}