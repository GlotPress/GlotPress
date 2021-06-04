<?php
/**
 * Translation warnings API for wporg
 *
 * @package GlotPress
 * @since 3.0.0
 */

/**
 * Class used to handle wporg translation warnings.
 *
 * @since 3.0.0
 */
class GP_Wporg_Translation_Warnings {
	/**
	 * List of domains with allowed changes to their own subdomains.
	 *
	 * This domains are injected in the GP_Builtin_Translation_Warnings class
	 * using the gp_allowed_domain_changes hook.
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @var array
	 */
	public $allowed_wporg_domain_changes = array(
		// Allow links to wordpress.org to be changed to a subdomain.
		'wordpress.org'    => '[^.]+\.wordpress\.org',
		// Allow links to wordpress.com to be changed to a subdomain.
		'wordpress.com'    => '[^.]+\.wordpress\.com',
		// Allow links to gravatar.org to be changed to a subdomain.
		'en.gravatar.com'  => '[^.]+\.gravatar\.com',
		// Allow links to wikipedia.org to be changed to a subdomain.
		'en.wikipedia.org' => '[^.]+\.wikipedia\.org',
	);

	/**
	 * GP_Wporg_Translation_Warnings constructor.
	 *
	 * @since 3.0.0
	 * @access public
	 */
	public function __construct() {
		$is_wporg = get_option( 'gp_is_wporg' );
		if ( false === $is_wporg ) {
			return;
		}

		add_filter( 'gp_allowed_domain_changes', array( $this, 'add_wporg_allowed_domains' ) );
		add_filter( 'gp_add_all_warnings', array( $this, 'add_wporg_warnings' ) );
	}

	/**
	 * Adds the allowed domains for the wordpress.org instance.
	 *
	 * Adds the allowed domains in the GP_Builtin_Translation_Warnings
	 * class using the gp_allowed_domain_changes hook.
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param array $domains
	 * @return array
	 */
	public function add_wporg_allowed_domains( array $domains ): array {
		$gp_domains = array();
		foreach ( $this->allowed_wporg_domain_changes as $key => $value ) {
			$gp_domains[ $key ] = $value;
		}
		return array_merge( $domains, $gp_domains );
	}

	/**
	 * Adds the warning methods.
	 *
	 * Adds the warning methods in the GP_Translation_Warnings using the
	 * gp_add_all_warnings hook of GP_Builtin_Translation_Warnings.
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param array $warnings
	 * @return array
	 */
	public function add_wporg_warnings( array $warnings ): array {
		$gp_warnings = array_filter(
			get_class_methods( get_class( $this ) ),
			function ( $key ) {
				return gp_startswith( $key, 'warning_' );
			}
		);
		$gp_warnings = array_fill_keys( $gp_warnings, $this );

		return array_merge( $warnings, $gp_warnings );
	}

	/**
	 * Adds a warning for changing placeholders.
	 *
	 * This only supports placeholders in the format of '###[A-Z_]+###'.
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param string $original    The original string.
	 * @param string $translation The translated string.
	 * @return string|true
	 */
	public function warning_wporg_mismatching_placeholders( string $original, string $translation ) {
		$placeholder_regex = '@(###[A-Z_]+###)@';

		preg_match_all( $placeholder_regex, $original, $original_placeholders );
		$original_placeholders = array_unique( $original_placeholders[0] );

		preg_match_all( $placeholder_regex, $translation, $translation_placeholders );
		$translation_placeholders = array_unique( $translation_placeholders[0] );

		$missing_placeholders = array_diff( $original_placeholders, $translation_placeholders );
		$added_placeholders   = array_diff( $translation_placeholders, $original_placeholders );
		if ( ! $missing_placeholders && ! $added_placeholders ) {
			return true;
		}

		$error = '';
		if ( $missing_placeholders ) {
			$error .= __( 'The translation appears to be missing the following placeholders: ', 'glotpress' ) . implode( ', ', $missing_placeholders ) . "\n";
		}
		if ( $added_placeholders ) {
			$error .= __( 'The translation contains the following unexpected placeholders: ', 'glotpress' ) . implode( ', ', $added_placeholders );
		}

		return trim( $error );
	}
}
