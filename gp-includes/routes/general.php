<?php
function gp_route_index( ) {
	$title = 'Welcome to GlotPress';
	gp_tmpl_page( 'index', get_defined_vars() );
}