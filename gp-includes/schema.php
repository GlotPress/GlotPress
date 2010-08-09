<?php
/**
 * Includes the database schema definitions and comments
 */

function gp_schema_get() {
	global $gpdb;
	$gp_schema = array();

	/*
	Translations
	 - There are fields to take all the plural forms (no known language has more than 4 plural forms)
	 - Belongs to an original string
	 - Belongs to a user
	 - Status can be: new, approved, unaproved, current, spam or whatever you'd like
	*/
	$gp_schema['translations'] = "CREATE TABLE IF NOT EXISTS `$gpdb->translations` (
		`id` INT(10) NOT NULL auto_increment,
		`original_id` INT(10) DEFAULT NULL,
		`translation_set_id` INT(10) DEFAULT NULL,
		`translation_0` TEXT NOT NULL,
		`translation_1` TEXT DEFAULT NULL,
		`translation_2` TEXT DEFAULT NULL,
		`translation_3` TEXT DEFAULT NULL,
		`translation_4` TEXT DEFAULT NULL,
		`translation_5` TEXT DEFAULT NULL,
		`user_id` INT(10) DEFAULT NULL,
		`status` VARCHAR(20) NOT NULL default 'waiting',
		`date_added` DATETIME DEFAULT NULL,
		`date_modified` DATETIME DEFAULT NULL,
		`warnings` TEXT DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `original_id` (`original_id`),
		KEY `user_id` (`user_id`),
		KEY `translation_set_id` (`translation_set_id`),
		KEY `translation_set_id_status` (`translation_set_id`,`status`),
		KEY `date_added` (`date_added`),
		KEY `warnings` (`warnings` (1))
	);";

    /*
    Translations sets: a translation set holds specific translation of a project in a specific language
    For example each WordPress Spanish translation (formal, informal and that of Diego) will be different sets.
    Most projects will have only one translation set per language.
    */
    $gp_schema['translation_sets'] = "CREATE TABLE IF NOT EXISTS `$gpdb->translation_sets` (
    	`id` INT(10) NOT NULL auto_increment,
    	`name` VARCHAR(255) NOT NULL,
    	`slug` VARCHAR(255) NOT NULL,
		`project_id` INT(10) DEFAULT NULL,
    	`locale` VARCHAR(10) DEFAULT NULL,
    	PRIMARY KEY (`id`),
    	UNIQUE KEY `project_id_slug_locale` (`project_id`, `slug`, `locale`)
    );";

	/*
	Original strings
	 - Has many translations
	 - Belongs to a project
	 */
	$gp_schema['originals'] = "CREATE TABLE IF NOT EXISTS `$gpdb->originals` (
		`id` INT(10) NOT NULL auto_increment,
		`project_id` INT(10) DEFAULT NULL,
		`context` VARCHAR(255) DEFAULT NULL,
		`singular` TEXT NOT NULL,
		`plural` TEXT DEFAULT NULL,
		`references` TEXT DEFAULT NULL,
		`comment` TEXT DEFAULT NULL,
		`status` VARCHAR(255) NOT NULL DEFAULT '+active',
		`priority` TINYINT NOT NULL DEFAULT 0,
		`date_added` DATETIME DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `project_id` (`project_id`)
	);";

	/*
	Projects
	- Has a project -- its parent
	- The path is the combination of the slugs of all its parents, separated by /
	*/
	$gp_schema['projects'] = "CREATE TABLE IF NOT EXISTS `$gpdb->projects` (
		`id` INT(10) NOT NULL auto_increment,
		`name` VARCHAR(255) NOT NULL,
		`slug` VARCHAR(255) NOT NULL,
		`path` VARCHAR(255) NOT NULL,
		`description` TEXT NOT NULL,
		`parent_project_id` INT(10) DEFAULT NULL,
		`source_url_template` VARCHAR(255) DEFAULT '',
		`active` TINYINT DEFAULT 0,
		PRIMARY KEY (`id`),
		KEY `path` (`path`),
		KEY `parent_project_id` (`parent_project_id`)
	);";

	/*
	Users
	 - Has many translations
	 - 'user_login', 'user_nicename' and 'user_registered' indices are inconsistent with WordPress
	*/
	$gp_schema['users'] = "CREATE TABLE IF NOT EXISTS `$gpdb->users` (
		`ID` bigINT(20) unsigned NOT NULL auto_increment,
		`user_login` varchar(60) NOT NULL default '',
		`user_pass` varchar(64) NOT NULL default '',
		`user_nicename` varchar(50) NOT NULL default '',
		`user_email` varchar(100) NOT NULL default '',
		`user_url` varchar(100) NOT NULL default '',
		`user_registered` datetime NOT NULL default '0000-00-00 00:00:00',
		`user_status` INT(11) NOT NULL default 0,
		`display_name` varchar(250) NOT NULL default '',
		PRIMARY KEY (`ID`),
		UNIQUE KEY `user_login` (`user_login`),
		UNIQUE KEY `user_nicename` (`user_nicename`),
		KEY `user_registered` (`user_registered`)
	);";
	if ( defined('CUSTOM_USER_TABLE') && $gpdb->users == CUSTOM_USER_TABLE ) unset( $gp_schema['users'] );

	// usermeta
	$gp_schema['usermeta'] = "CREATE TABLE IF NOT EXISTS `$gpdb->usermeta` (
		`umeta_id` bigINT(20) NOT NULL auto_increment,
		`user_id` bigINT(20) NOT NULL default 0,
		`meta_key` varchar(255) NOT NULL,
		`meta_value` longTEXT NOT NULL,
		PRIMARY KEY (`umeta_id`),
		KEY `user_id` (`user_id`),
		KEY `meta_key` (`meta_key`)
	);";
	
	// meta
	$gp_schema['meta'] = "CREATE TABLE IF NOT EXISTS `$gpdb->meta` (
		`meta_id` bigint(20) NOT NULL auto_increment,
		`object_type` varchar(16) NOT NULL default 'gp_option',
		`object_id` bigint(20) NOT NULL default 0,
		`meta_key` varchar(255) DEFAULT NULL,
		`meta_value` longtext DEFAULT NULL,
		PRIMARY KEY (`meta_id`),
		KEY `object_type__meta_key` (`object_type`, `meta_key`),
		KEY `object_type__object_id__meta_key` (`object_type`, `object_id`, `meta_key`)
	);";
	
	// permissions
	$gp_schema['permissions'] = "CREATE TABLE IF NOT EXISTS `$gpdb->permissions` (
		`id` INT(10) NOT NULL AUTO_INCREMENT,
		`user_id` INT(10) DEFAULT NULL,
		`action` VARCHAR(255) DEFAULT NULL,
		`object_type` VARCHAR(255) DEFAULT NULL,
		`object_id` VARCHAR(255) DEFAULT NULL,
		PRIMARY KEY (`id`),
		KEY `user_id_action` (`user_id`,`action`)
	);";

	// API keys
	$gp_schema['api_keys'] = "CREATE TABLE IF NOT EXISTS `$gpdb->api_keys` (
		`id` INT(10) NOT NULL AUTO_INCREMENT,
		`user_id` INT(10) NOT NULL,
		`api_key` VARCHAR(16) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `user_id` (`user_id`),
		UNIQUE KEY `api_key` (`api_key`)
	);";

	$gp_schema = apply_filters( 'gp_schema_pre_charset', $gp_schema );

	// Set the charset and collation on each table
	foreach ($gp_schema as $_table_name => $_sql) {
		// Skip SQL that isn't creating a table
		if (!preg_match('@^\s*CREATE\s+TABLE\s+@im', $_sql)) {
			continue;
		}
	
		// Skip if the table's database doesn't support collation
		if (!$gpdb->has_cap('collation', $gpdb->$_table_name)) {
			continue;
		}
	
		// Find out if the table has a custom database set
		if (
			isset($gpdb->db_tables) &&
			is_array($gpdb->db_tables) &&
			isset($gpdb->db_tables[$gpdb->$_table_name])
		) {
			// Set the database for this table
			$_database = $gpdb->db_tables[$gpdb->$_table_name];
		} else {
			// Set the default global database
			$_database = 'dbh_global';
		}
	
		// Make sure the database exists
		if (
			isset($gpdb->db_servers) &&
			is_array($gpdb->db_servers) &&
			isset($gpdb->db_servers[$_database]) &&
			is_array($gpdb->db_servers[$_database])
		) {
			$_charset_collate = '';
			if (isset($gpdb->db_servers[$_database]['charset']) && !empty($gpdb->db_servers[$_database]['charset'])) {
				// Add a charset if set
				$_charset_collate .= ' DEFAULT CHARACTER SET \'' . $gpdb->db_servers[$_database]['charset'] . '\'';
			}
			if (isset($gpdb->db_servers[$_database]['collate']) && !empty($gpdb->db_servers[$_database]['collate'])) {
				// Add a collation if set
				$_charset_collate .= ' COLLATE \'' . $gpdb->db_servers[$_database]['collate'] . '\'';
			}
			if ($_charset_collate) {
				// Modify the SQL
				$gp_schema[$_table_name] = str_replace(';', $_charset_collate . ';', $_sql);
			}			
		}
		unset($_database, $_charset_collate);
	}
	unset($_table_name, $_sql);

	$gp_schema = apply_filters( 'gp_schema', $gp_schema );

	return $gp_schema;
}