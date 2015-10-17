<?php
/**
 * Includes the database schema definitions and comments
 */

function gp_schema_get() {
	global $wpdb;
	$gp_schema = array();


	$charset_collate = '';
	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";

	/*
	 * Indexes have a maximum size of 767 bytes. Historically, we haven't need to be concerned about that.
	 * As of 4.2, however, we moved to utf8mb4, which uses 4 bytes per character. This means that an index which
	 * used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 */
	$max_index_length = 191;

	/*
	Translations
	 - There are fields to take all the plural forms (no known locale has more than 4 plural forms)
	 - Belongs to an original string
	 - Belongs to a user
	 - Status can be: new, approved, unaproved, current, spam or whatever you'd like
	*/
	$gp_schema['translations'] = "CREATE TABLE $wpdb->gp_translations (
		id INT(10) NOT NULL auto_increment,
		original_id INT(10) DEFAULT NULL,
		translation_set_id INT(10) DEFAULT NULL,
		translation_0 TEXT NOT NULL,
		translation_1 TEXT DEFAULT NULL,
		translation_2 TEXT DEFAULT NULL,
		translation_3 TEXT DEFAULT NULL,
		translation_4 TEXT DEFAULT NULL,
		translation_5 TEXT DEFAULT NULL,
		user_id INT(10) DEFAULT NULL,
		status VARCHAR(20) NOT NULL default 'waiting',
		date_added DATETIME DEFAULT NULL,
		date_modified DATETIME DEFAULT NULL,
		warnings TEXT DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY original_id (original_id),
		KEY user_id (user_id),
		KEY translation_set_id (translation_set_id),
		KEY translation_set_id_status (translation_set_id,status),
		KEY date_added (date_added),
		KEY warnings (warnings (1))
	) $charset_collate;";

	/*
	Translations sets: A "translation set" holds all translated strings within a project for a specific locale.
	For example each WordPress Spanish translation (formal, informal and that of Diego) will be different sets.
	Most projects will have only one translation set per locale.
	*/
	$gp_schema['translation_sets'] = "CREATE TABLE $wpdb->gp_translation_sets (
		id INT(10) NOT NULL auto_increment,
		name VARCHAR(255) NOT NULL,
		slug VARCHAR(255) NOT NULL,
		project_id INT(10) DEFAULT NULL,
		locale VARCHAR(10) DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY project_id_slug_locale (project_id, slug(171), locale),
		KEY locale_slug (locale, slug(181))
	) $charset_collate;";

	/*
	Original strings
	 - Has many translations
	 - Belongs to a project
	 */
	$gp_schema['originals'] = "CREATE TABLE $wpdb->gp_originals (
		id INT(10) NOT NULL auto_increment,
		project_id INT(10) DEFAULT NULL,
		context VARCHAR(255) DEFAULT NULL,
		singular TEXT NOT NULL,
		plural TEXT DEFAULT NULL,
		`references` TEXT DEFAULT NULL,
		comment TEXT DEFAULT NULL,
		status VARCHAR(255) NOT NULL DEFAULT '+active',
		priority TINYINT NOT NULL DEFAULT 0,
		date_added DATETIME DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY project_id_status (project_id, status),
		KEY singular_plural_context (singular(63), plural(63), context(63))
	) $charset_collate;";

	/*
	Glossary Entries
	*/
	$gp_schema['glossary_entries'] = "CREATE TABLE $wpdb->gp_glossary_entries (
		id INT(10) unsigned NOT NULL auto_increment,
		glossary_id INT(10) unsigned NOT NULL,
		term VARCHAR(255) NOT NULL,
		part_of_speech VARCHAR(255) DEFAULT NULL,
		comment TEXT DEFAULT NULL,
		translation VARCHAR(255) DEFAULT NULL,
		date_modified DATETIME NOT NULL,
		last_edited_by BIGINT(20) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	/*
	Glossaries
	*/
	$gp_schema['glossaries'] = "CREATE TABLE $wpdb->gp_glossaries (
		id INT(10) unsigned NOT NULL auto_increment,
		translation_set_id INT(10)  NOT NULL,
		description TEXT DEFAULT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	/*
	Projects
	- Has a project -- its parent
	- The path is the combination of the slugs of all its parents, separated by /
	*/
	$gp_schema['projects'] = "CREATE TABLE $wpdb->gp_projects (
		id INT(10) NOT NULL auto_increment,
		name VARCHAR(255) NOT NULL,
		slug VARCHAR(255) NOT NULL,
		path VARCHAR(255) NOT NULL,
		description TEXT NOT NULL,
		parent_project_id INT(10) DEFAULT NULL,
		source_url_template VARCHAR(255) DEFAULT '',
		active TINYINT DEFAULT 0,
		PRIMARY KEY  (id),
		KEY path (path),
		KEY parent_project_id (parent_project_id)
	) $charset_collate;";

	// meta
	$gp_schema['meta'] = "CREATE TABLE $wpdb->gp_meta (
		meta_id bigint(20) NOT NULL auto_increment,
		object_type varchar(32) NOT NULL default 'gp_option',
		object_id bigint(20) NOT NULL default 0,
		meta_key varchar($max_index_length) DEFAULT NULL,
		meta_value longtext DEFAULT NULL,
		PRIMARY KEY  (meta_id),
		KEY object_type__meta_key (object_type, meta_key),
		KEY object_type__object_id__meta_key (object_type, object_id, meta_key)
	) $charset_collate;";

	// permissions
	$gp_schema['permissions'] = "CREATE TABLE $wpdb->gp_permissions (
		id INT(10) NOT NULL AUTO_INCREMENT,
		user_id INT(10) DEFAULT NULL,
		action VARCHAR(255) DEFAULT NULL,
		object_type VARCHAR(255) DEFAULT NULL,
		object_id VARCHAR(255) DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY user_id_action (user_id,action)
	) $charset_collate;";

	// API keys
	$gp_schema['api_keys'] = "CREATE TABLE $wpdb->gp_api_keys (
		id INT(10) NOT NULL AUTO_INCREMENT,
		user_id INT(10) NOT NULL,
		api_key VARCHAR(16) NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY user_id (user_id),
		UNIQUE KEY api_key (api_key)
	) $charset_collate;";

	$gp_schema = apply_filters( 'gp_schema', $gp_schema );

	return $gp_schema;
}
