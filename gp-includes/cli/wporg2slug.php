<?php

class GP_CLI_WPorg2Slug extends WP_CLI_Command {

	var $usage = "<wporg-locale>";

	/**
	 * Get the slug from a WPorg slug
	 *
	 * ## OPTIONS
	 *
	 * <wporg-locale>
	 * : WP.org locale slug
	 */
	public function __invoke( $args ) {
		$wporg_slug = $args[0];
		$slug = null;

		foreach( GP_Locales::locales() as $locale ) {
			if ( $locale->wp_locale == $wporg_slug ) {
				$slug = $locale->slug;
				break;
			}
		}

		if ( ! $slug ) {
			WP_CLI::error("No slug match for $wporg_slug.");
		}

		echo $slug;
	}
}
