<?php

function gp_link_get( $url, $text, $attrs = array() ) {
	$before = $after = '';
	foreach ( array('before', 'after') as $key ) {
		if ( isset( $attrs[$key] ) ) {
			$$key = $attrs[$key];
			unset( $attrs[$key] );
		}
	}
	$attributes = gp_html_attributes( $attrs );
	$attributes = $attributes? " $attributes" : '';
	// TODO: clean_url(), but make it allow [ and ]
	return sprintf('%1$s<a href="%2$s"%3$s>%4$s</a>%5$s', $before, $url, $attributes, $text, $after );
}

function gp_link() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_get', $args);
}

function gp_link_project_get( $project_or_path, $text, $attrs = array() ) {
	$attrs = array_merge( array( 'title' => 'Project: '.$text ), $attrs );
	return gp_link_get( gp_url_project( $project_or_path ), $text, $attrs );
}

function gp_link_project() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_project_get', $args);
}

function gp_link_project_edit_get( $project, $text = null, $attrs = array() ) {
	if ( !GP::$user->current()->can( 'write', 'project', $project->id ) ) {
		return '';
	}
	$text = $text? $text : __( 'Edit' );
	return gp_link_get( gp_url_project( $project, '-edit' ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_project_edit() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_project_edit_get', $args);
}

function gp_link_project_delete_get( $project, $text = false, $attrs = array() ) {
	if ( !GP::$user->current()->can( 'write', 'project', $project->id ) ) {
		return '';
	}
	$text = $text? $text : __( 'Delete' );
	return gp_link_get( gp_url_project( $project, '-delete' ), $text, gp_attrs_add_class( $attrs, 'action delete' ) );
}

function gp_link_project_delete() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_project_delete_get', $args);
}

function gp_link_home_get() {
	return gp_link_get( gp_url( '/' ), __( 'Home' ), array( 'title' => __('Home Is Where The Heart Is') ) );
}

function gp_link_home() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_home_get', $args);
}

function gp_link_login_get() {
	return gp_link_get( gp_url( '/login' ), __( 'Login' ), array( 'title' => __('Sign into GlotPress') ) );
}

function gp_link_login() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_login_get', $args);
}

function gp_link_set_edit_get( $set, $project, $text = false, $attrs = array() ) {
	if ( !GP::$user->current()->can( 'write', 'project', $project->id ) ) {
		return '';
	}
	$text = $text? $text : __( 'Edit' );
	return gp_link_get( gp_url( gp_url_join( '/sets', $set->id, '-edit' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_set_edit() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_set_edit_get', $args);
}
