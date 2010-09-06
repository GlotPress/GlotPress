<?php
/**
 * Localization. Loading and using translations.
 */


function get_locale() {
	global $locale;

	if (isset($locale))
		return apply_filters( 'locale', $locale );

	// GP_LANG is defined in gp-config.
	if ( defined('GP_LANG') )
		$locale = GP_LANG;
		
	// TODO: get locale from DB

	if (empty($locale))
		$locale = 'en_US';

	$locale = apply_filters('locale', $locale);

	return $locale;
}

function translate( $text, $domain = 'default' ) {
	$translations = &get_translations_for_domain( $domain );
	return apply_filters('gettext', $translations->translate($text), $text, $domain);
}

function translate_with_gettext_context( $text, $context, $domain = 'default' ) {
	$translations = &get_translations_for_domain( $domain );
	return apply_filters( 'gettext_with_context', $translations->translate( $text, $context ), $text, $context, $domain);
}

function __( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}

function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( translate( $text, $domain ) );
}

function esc_html__( $text, $domain = 'default' ) {
	return esc_html( translate( $text, $domain ) );
}

function _e( $text, $domain = 'default' ) {
	echo translate( $text, $domain );
}

function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( translate( $text, $domain ) );
}

function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( translate( $text, $domain ) );
}

function _x( $single, $context, $domain = 'default' ) {
	return translate_with_gettext_context( $single, $context, $domain );
}

function esc_attr_x( $single, $context, $domain = 'default' ) {
	return esc_attr( translate_with_gettext_context( $single, $context, $domain ) );
}

function esc_html_x( $single, $context, $domain = 'default' ) {
	return esc_html( translate_with_gettext_context( $single, $context, $domain ) );
}

function _n($single, $plural, $number, $domain = 'default') {
	$translations = &get_translations_for_domain( $domain );
	$translation = $translations->translate_plural( $single, $plural, $number );
	return apply_filters( 'ngettext', $translation, $single, $plural, $number );
}

function _nx($single, $plural, $number, $context, $domain = 'default') {
	$translations = &get_translations_for_domain( $domain );
	$translation = $translations->translate_plural( $single, $plural, $number, $context );
	return apply_filters( 'ngettext_with_context ', $translation, $single, $plural, $number, $context );
}

function _n_noop( $single, $plural, $number = 1, $domain = 'default' ) {
	return array( $single, $plural );
}

function load_textdomain( $domain, $mofile ) {
	global $l10n;

	if ( !is_readable( $mofile ) ) return;
	
	$mo = new MO();
	$mo->import_from_file( $mofile );

	if ( isset( $l10n [$domain] ) )
		$mo->merge_with( $l10n[$domain] );
		
	$l10n[$domain] = &$mo;
}

function load_default_textdomain() {
	$locale = get_locale();

	$mofile = GP_LANG_PATH . "/$locale.mo";

	load_textdomain('default', $mofile);
}

/**
 * Returns the Translations instance for a domain. If there isn't one,
 * returns empty Translations instance.
 *
 * @since 1.0.0
 *
 * @param string $domain
 * @return object A Translation instance
 */
function get_translations_for_domain( $domain ) {
	global $l10n;
	$empty = new Translations;
	return isset($l10n[$domain])? $l10n[$domain] : $empty;
}