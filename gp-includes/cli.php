<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	gp_cli_register();
}

function gp_cli_register() {
	require_once GP_PATH . GP_INC . 'cli/add-admin.php';
	require_once GP_PATH . GP_INC . 'cli/branch-project.php';
	require_once GP_PATH . GP_INC . 'cli/import-originals.php';
	require_once GP_PATH . GP_INC . 'cli/regenerate-paths.php';
	require_once GP_PATH . GP_INC . 'cli/remove-multiple-currents.php';
	require_once GP_PATH . GP_INC . 'cli/translation-set.php';
	require_once GP_PATH . GP_INC . 'cli/upgrade-set-permissions.php';
	require_once GP_PATH . GP_INC . 'cli/wipe-permissions.php';

	// Legacy commands.
	WP_CLI::add_command( 'glotpress add-admin', 'GP_CLI_Add_Admin' );
	WP_CLI::add_command( 'glotpress branch-project', 'GP_CLI_Branch_Project' );
	WP_CLI::add_command( 'glotpress import-originals', 'GP_CLI_Import_Originals' );
	WP_CLI::add_command( 'glotpress regenerate-paths', 'GP_CLI_Regenerate_Paths' );
	WP_CLI::add_command( 'glotpress remove-multiple-currents', 'GP_CLI_Remove_Multiple_Currents' );
	WP_CLI::add_command( 'glotpress upgrade-set-permissions', 'GP_CLI_Upgrade_Set_Permissions' );
	WP_CLI::add_command( 'glotpress wipe-permissions', 'GP_CLI_Wipe_Permissions' );

	// New style commands.
	WP_CLI::add_command( 'glotpress translation-set', 'GP_CLI_Translation_Set' );

	// CLI related filters.
	add_filter( 'gp_pre_can_set_translation_status', '__return_true' );
}
