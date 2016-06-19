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

/**
 * Creates a HTML link to the delete page for translations sets.
 *
 * Does the heavy lifting for gp_link_set_delete().
 *
 * @since 2.0.0
 *
 * @param GP_Translation_Set $set     The translation set to link to.
 * @param GP_Project         $project The project the translation set belongs to.
 * @param string             $text    The text to use for the link.
 * @param array              $attrs   Additional attributes to use to determine the classes for the link.
 *
 * @return string $link
 */
function gp_link_set_delete_get( $set, $project, $text = false, $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'delete', 'project', $project->id ) ) {
		return '';
	}

	if ( false === $text ) {
		$text = __( 'Delete', 'glotpress' );
	}

	return gp_link_get( gp_url( gp_url_join( '/sets', $set->id, '-delete' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the delete page for translations sets.
 *
 * Does the heavy lifting for gp_link_set_delete().
 *
 * @since 2.0.0
 *
 * @param GP_Translation_Set $set     The translation set to link to.
 * @param GP_Project         $project The project the translation set belongs to.
 * @param string             $text    The text to use for the link.
 * @param array              $attrs   Additional attributes to use to determine the classes for the link.
 */
function gp_link_set_delete( $set, $project, $text = false, $attrs = array() ) {
	echo gp_link_set_delete_get( $set, $project, $text, $attrs );
}

/**
 * Creates a HTML link to the delete page for translations sets.
 *
 * Does the heavy lifting for gp_link_glossary_delete.
 *
 * @since 1.0.0
 *
 * @param GP_Glossary        $glossary The glossary to link to.
 * @param GP_Translation_Set $set      The translation set the glossary is for.
 * @param string             $text     The text to use for the link.
 * @param array              $attrs    Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
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

/**
 * Creates a HTML link to the delete page for translations sets.
 *
 * Does the heavy lifting for gp_link_glossary_delete.
 *
 * @since 2.0.0
 *
 * @param GP_Glossary        $glossary The glossary to link to.
 * @param GP_Translation_Set $set      The translation set the glossary is for.
 * @param string             $text     The text to use for the link.
 * @param array              $attrs    Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_glossary_delete_get( $glossary, $set, $text = false, $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'delete', 'translation-set', $set->id ) ) {
		return '';
	}

	$text = $text? $text : __( 'Delete', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/glossaries', $glossary->id, '-delete' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the delete page for glossaries.
 *
 * @since 2.0.0
 *
 * @param GP_Glossary        $glossary The glossary to link to.
 * @param GP_Translation_Set $set      The translation set the glossary is for.
 * @param string             $text     The text to use for the link.
 * @param array              $attrs    Additional attributes to use to determine the classes for the link.
 */
function gp_link_glossary_delete( $glossary, $set, $text = false, $attrs = array() ) {
	echo gp_link_glossary_delete_get( $glossary, $set, $text, $attrs );
}

/**
 * Outputs a HTML link to a user profile page.
 *
 * @since 2.1.0
 *
 * @param WP_User $user A WP_User user object.
 */
function gp_link_user( $user ) {
	if ( $user->display_name && $user->display_name !== $user->user_login ) {
		printf( '<a href="%s" tabindex="-1">%s (%s)</a>',
			esc_url( gp_url_profile( $user->user_nicename ) ),
			esc_html( $user->display_name ),
			esc_html( $user->user_login )
		);
	} else {
		printf( '<a href="%s" tabindex="-1">%s</a>',
			esc_url( gp_url_profile( $user->user_nicename ) ),
			esc_attr( $user->user_login )
		);
	}
}
