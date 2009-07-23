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
	return sprintf('%1$s<a href="%2$s"%3$s>%4$s</a>%5$s', $before, clean_url( $url ), $attributes, $text, $after );
}

function gp_link() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_get', $args);
}

function gp_link_project_get( &$project_or_slug, $text, $attrs = array() ) {
	return gp_link_get( gp_url_project( $project_or_slug ), $text, $attrs );
}

function gp_link_project() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_project_get', $args);
}

function gp_link_project_edit_get( &$project_or_path, $text = false, $attrs = array() ) {
	// TODO: check proper permissions
	if ( !GP_User::current()->can('admin')) {
		return '';
	}
	$text = $text? $text : __( 'Edit' );
	return gp_link_get( gp_url_project( $project_or_path, '_edit' ), $text, gp_attrs_add_class( $attrs, 'edit' ) );
}

function gp_link_project_edit() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_project_edit_get', $args);
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


function gp_html_attributes( $attrs ) {
	$attrs = wp_parse_args( $attrs );
	$strings = array();
	foreach( $attrs as $key => $value ) {
		$strings[] = $key.'="'.esc_attr( $value ).'"';
	}
	return implode( ' ', $strings );
}

function gp_attrs_add_class( $attrs, $class_name ) {
	$attrs['class'] = isset( $attrs['class'] )? $attrs['class'] . ' ' . $class_name : $class_name;
	return $attrs;
}