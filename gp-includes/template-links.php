<?php
/**
 * Link Template Functions
 *
 * @package GlotPress
 * @subpackage Template
 */

/**
 * Creates a HTML link.
 *
 * @since 1.0.0
 *
 * @param string $url   The URL to link to.
 * @param string $text  The text to use for the link.
 * @param array  $attrs Optional. Additional attributes to use
 *                      to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_get( $url, $text, $attrs = array() ) {
	$before = '';
	$after  = '';
	foreach ( array( 'before', 'after' ) as $key ) {
		if ( isset( $attrs[ $key ] ) ) {
			$$key = $attrs[ $key ];
			unset( $attrs[ $key ] );
		}
	}
	$attributes = gp_html_attributes( $attrs );
	$attributes = $attributes ? " $attributes" : '';

	return sprintf( '%1$s<a href="%2$s"%3$s>%4$s</a>%5$s', $before, esc_url( $url ), $attributes, $text, $after );
}

/**
 * Creates a HTML link.
 *
 * @since 1.0.0
 *
 * @see gp_link_get()
 *
 * @param string $url   The URL to link to.
 * @param string $text  The text to use for the link.
 * @param array  $attrs Optional. Additional attributes to use
 *                      to determine the classes for the link.
 */
function gp_link( $url, $text, $attrs = array() ) {
	echo gp_link_get( $url, $text, $attrs );
}

/**
 * Creates a HTML link with a confirmation request.
 *
 * Uses the `window.confirm()` method to display a modal dialog for confirmation.
 *
 * @since 1.0.0
 *
 * @param string $url   The URL to link to.
 * @param string $text  The text to use for the link.
 * @param array  $attrs Optional. Additional attributes to use
 *                      to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_with_ays_get( $url, $text, $attrs = array() ) {
	$ays_text = $attrs['ays-text'];
	unset( $attrs['ays-text'] );
	$attrs['onclick'] = "return confirm('" . esc_js( $ays_text ) . "');";
	return gp_link_get( $url, $text, $attrs );
}

/**
 * Creates a HTML link with a confirmation request.
 *
 * Uses the `window.confirm()` method to display a modal dialog for confirmation.
 *
 * @since 1.0.0
 *
 * @see gp_link_with_ays_get()
 *
 * @param string $url   The URL to link to.
 * @param string $text  The text to use for the link.
 * @param array  $attrs Optional. Additional attributes to use
 *                      to determine the classes for the link.
 */
function gp_link_with_ays( $url, $text, $attrs = array() ) {
	echo gp_link_with_ays_get( $url, $text, $attrs );
}

/**
 * Creates a HTML link to the page of a project.
 *
 * @since 1.0.0
 *
 * @param GP_Project|string $project_or_path The project to link to.
 * @param string            $text            The text to use for the link.
 * @param array             $attrs           Optional. Additional attributes to use
 *                                           to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_project_get( $project_or_path, $text, $attrs = array() ) {
	return gp_link_get( gp_url_project( $project_or_path ), $text, $attrs );
}

/**
 * Outputs a HTML link to the page of a project.
 *
 * @since 1.0.0
 *
 * @see gp_link_project_get()
 *
 * @param GP_Project|string $project_or_path The project to link to.
 * @param string            $text            The text to use for the link.
 * @param array             $attrs           Optional. Additional attributes to use
 *                                           to determine the classes for the link.
 */
function gp_link_project( $project_or_path, $text, $attrs = array() ) {
	echo gp_link_project_get( $project_or_path, $text, $attrs );
}

/**
 * Creates a HTML link to the edit page for projects.
 *
 * @since 1.0.0
 *
 * @param GP_Project $project The project to link to.
 * @param string     $text    Optional. The text to use for the link. Default 'Edit'.
 * @param array      $attrs   Optional. Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_project_edit_get( $project, $text = '', $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'write', 'project', $project->id ) ) {
		return '';
	}
	$text = $text ? $text : __( 'Edit', 'glotpress' );
	return gp_link_get( gp_url_project( $project, '-edit' ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the edit page for projects.
 *
 * @since 1.0.0
 *
 * @see gp_link_project_edit_get()
 *
 * @param GP_Project $project The project to link to.
 * @param string     $text    Optional. The text to use for the link. Default 'Edit'.
 * @param array      $attrs   Optional. Additional attributes to use to determine the classes for the link.
 */
function gp_link_project_edit( $project, $text = '', $attrs = array() ) {
	echo gp_link_project_edit_get( $project, $text, $attrs );
}

/**
 * Creates a HTML link to the delete page for projects.
 *
 * @since 1.0.0
 *
 * @param GP_Project $project The project to link to.
 * @param string     $text    Optional. The text to use for the link. Default 'Delete'.
 * @param array      $attrs   Optional. Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_project_delete_get( $project, $text = '', $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'delete', 'project', $project->id ) ) {
		return '';
	}
	$text = $text ? $text : __( 'Delete', 'glotpress' );
	return gp_link_get( gp_url_project( $project, '-delete' ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the delete page for projects.
 *
 * @since 1.0.0
 *
 * @see gp_link_project_delete_get()
 *
 * @param GP_Project $project The project to link to.
 * @param string     $text    Optional. The text to use for the link.
 * @param array      $attrs   Optional. Additional attributes to use to determine the classes for the link.
 */
function gp_link_project_delete( $project, $text = '', $attrs = array() ) {
	echo gp_link_project_delete_get( $project, $text, $attrs );
}

/**
 * Creates a HTML link to the home page of GlotPress.
 *
 * @since 1.0.0
 *
 * @return string The HTML link.
 */
function gp_link_home_get() {
	return gp_link_get( gp_url( '/' ), __( 'Home', 'glotpress' ) );
}

/**
 * Outputs a HTML link to the home page of GlotPress.
 *
 * @since 1.0.0
 *
 * @see gp_link_home_get()
 */
function gp_link_home() {
	echo gp_link_home_get();
}

/**
 * Creates a HTML link to the delete page for translations sets.
 *
 * @since 1.0.0
 *
 * @param GP_Translation_Set $set     The translation set to link to.
 * @param GP_Project         $project The project the translation set belongs to.
 * @param string             $text    Optional. The text to use for the link. Default 'Edit'.
 * @param array              $attrs   Optional. Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_set_edit_get( $set, $project, $text = '', $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'write', 'project', $project->id ) ) {
		return '';
	}

	$text = $text ? $text : __( 'Edit', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/sets', $set->id, '-edit' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the edit page for translations sets.
 *
 * @since 1.0.0
 *
 * @see gp_link_set_edit_get()
 *
 * @param GP_Translation_Set $set     The translation set to link to.
 * @param GP_Project         $project The project the translation set belongs to.
 * @param string             $text    Optional. The text to use for the link. Default 'Edit'.
 * @param array              $attrs   Optional. Additional attributes to use to determine the classes for the link.
 */
function gp_link_set_edit( $set, $project, $text = '', $attrs = array() ) {
	echo gp_link_set_edit_get( $set, $project, $text, $attrs );
}

/**
 * Creates a HTML link to the delete page for translations sets.
 *
 * @since 2.0.0
 *
 * @param GP_Translation_Set $set     The translation set to link to.
 * @param GP_Project         $project The project the translation set belongs to.
 * @param string             $text    Optional. The text to use for the link. Default 'Delete'.
 * @param array              $attrs   Optional. Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_set_delete_get( $set, $project, $text = '', $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'delete', 'project', $project->id ) ) {
		return '';
	}

	$text = $text ? $text : __( 'Delete', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/sets', $set->id, '-delete' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the delete page for translations sets.
 *
 * @since 2.0.0
 *
 * @see gp_link_set_delete_get()
 *
 * @param GP_Translation_Set $set     The translation set to link to.
 * @param GP_Project         $project The project the translation set belongs to.
 * @param string             $text    Optional. The text to use for the link. Default 'Delete'.
 * @param array              $attrs   Optional. Additional attributes to use to determine the classes for the link.
 */
function gp_link_set_delete( $set, $project, $text = '', $attrs = array() ) {
	echo gp_link_set_delete_get( $set, $project, $text, $attrs );
}

/**
 * Creates a HTML link to the edit page for glossaries.
 *
 * @since 1.0.0
 *
 * @param GP_Glossary        $glossary The glossary to link to.
 * @param GP_Translation_Set $set      The translation set the glossary is for.
 * @param string             $text     Optional. The text to use for the link. Default 'Edit'.
 * @param array              $attrs    Optional. Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_glossary_edit_get( $glossary, $set, $text = '', $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'approve', 'translation-set', $set->id ) ) {
		return '';
	}

	$text = $text ? $text : __( 'Edit', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/glossaries', $glossary->id, '-edit' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the edit page for glossaries.
 *
 * @since 1.0.0
 *
 * @see gp_link_glossary_edit_get()
 *
 * @param GP_Glossary        $glossary The glossary to link to.
 * @param GP_Translation_Set $set      The translation set the glossary is for.
 * @param string             $text     Optional. The text to use for the link. Default 'Edit'.
 * @param array              $attrs    Optional. Additional attributes to use to determine the classes for the link.
 */
function gp_link_glossary_edit( $glossary, $set, $text = '', $attrs = array() ) {
	echo gp_link_glossary_edit_get( $glossary, $set, $text, $attrs );
}

/**
 * Creates a HTML link to the delete page for glossaries.
 *
 * @since 2.0.0
 *
 * @param GP_Glossary        $glossary The glossary to link to.
 * @param GP_Translation_Set $set      The translation set the glossary is for.
 * @param string             $text     Optional. The text to use for the link. Default 'Delete'.
 * @param array              $attrs    Optional. Additional attributes to use to determine the classes for the link.
 * @return string The HTML link.
 */
function gp_link_glossary_delete_get( $glossary, $set, $text = '', $attrs = array() ) {
	if ( ! GP::$permission->current_user_can( 'delete', 'translation-set', $set->id ) ) {
		return '';
	}

	$text = $text ? $text : __( 'Delete', 'glotpress' );
	return gp_link_get( gp_url( gp_url_join( '/glossaries', $glossary->id, '-delete' ) ), $text, gp_attrs_add_class( $attrs, 'action edit' ) );
}

/**
 * Outputs a HTML link to the delete page for glossaries.
 *
 * @since 2.0.0
 *
 * @see gp_link_glossary_delete_get()
 *
 * @param GP_Glossary        $glossary The glossary to link to.
 * @param GP_Translation_Set $set      The translation set the glossary is for.
 * @param string             $text     Optional. The text to use for the link. Default 'Delete'.
 * @param array              $attrs    Optional. Additional attributes to use to determine the classes for the link.
 */
function gp_link_glossary_delete( $glossary, $set, $text = '', $attrs = array() ) {
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
		printf(
			'<a href="%s" tabindex="-1">%s (%s)</a>',
			esc_url( gp_url_profile( $user->user_nicename ) ),
			esc_html( $user->display_name ),
			esc_html( $user->user_login )
		);
	} else {
		printf(
			'<a href="%s" tabindex="-1">%s</a>',
			esc_url( gp_url_profile( $user->user_nicename ) ),
			esc_attr( $user->user_login )
		);
	}
}
