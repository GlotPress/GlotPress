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

	return sprintf('%1$s<a href="%2$s"%3$s>%4$s</a>%5$s', $before, esc_url( $url ), $attributes, $text, $after );
}

function gp_link() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_get', $args);
}

function gp_link_with_ays_get( $url, $text, $attrs = array() ) {
	$ays_text = $attrs['ays-text'];
	unset( $attrs['ays-text'] );
	$attrs['onclick'] = "return confirm('".esc_js( $ays_text )."');";
	return gp_link_get( $url, $text, $attrs );
}

function gp_link_with_ays() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_with_ays_get', $args);
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
	if ( ! GP::$permission->current_user_can( 'write', 'project', $project->id ) ) {
		return '';
	}
	$text = $text? $text : __( 'Edit', 'glotpress' );
	return gp_link_get( gp_url_project( $project, '-edit' ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_project_edit() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_project_edit_get', $args);
}

function gp_link_project_delete_get( $project, $text = false, $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'delete', 'project', $project->id ) ) {
		return '';
	}
	$text = $text? $text : __( 'Delete', 'glotpress' );
	return gp_link_get( gp_url_project( $project, '-delete' ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_project_delete() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_project_delete_get', $args);
}

function gp_link_home_get() {
	return gp_link_get( gp_url( '/' ), __( 'Home', 'glotpress' ), array( 'title' => __( 'Home Is Where The Heart Is', 'glotpress' ) ) );
}

function gp_link_home() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_home_get', $args);
}

function gp_link_set_edit_get( $set, $project, $text = false, $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'write', 'project', $project->id ) ) {
		return '';
	}
	$text = $text? $text : __( 'Edit', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/sets', $set->id, '-edit' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_set_edit() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_set_edit_get', $args);
}

function gp_link_set_delete_get( $set, $project, $text = false, $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'write', 'project', $project->id ) ) {
		return '';
	}
	$text = $text? $text : __( 'Delete', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/sets', $set->id, '-delete' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_set_delete() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_set_delete_get', $args);
}

function gp_link_glossary_edit_get( $glossary, $set, $text = false, $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'approve', 'translation-set', $set->id ) ) {
		return '';
	}

	$text = $text? $text : __( 'Edit', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/glossaries', $glossary->id, '-edit' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_glossary_edit() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_glossary_edit_get', $args);
}

function gp_link_glossary_delete_get( $glossary, $set, $text = false, $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'approve', 'translation-set', $set->id ) ) {
		return '';
	}

	$text = $text? $text : __( 'Delete', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/glossaries', $glossary->id, '-delete' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

function gp_link_glossary_delete() {
	$args = func_get_args();
	echo call_user_func_array('gp_link_glossary_delete_get', $args);
}
