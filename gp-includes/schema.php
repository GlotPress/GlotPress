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
	 * Indexes have a maximum size of 767 bytes in the MyISAM database engine. Historically, we haven't needed to be overtly
	 * concerned about that.
	 *
	 * As of WordPress 4.2, however, utf8mb4 is now the default collation which uses 4 bytes per character. This means that
	 * an index which used to have room for floor(767/3) = 255 characters, now only has room for floor(767/4) = 191 characters.
	 *
	 * See https://dev.mysql.com/doc/refman/5.7/en/storage-requirements.html for details on the storage requirements
	 * for each data type.
	 */
	$max_index_characters = 767;
	$bytes_per_character = 4;
	$max_index_length = floor( $max_index_characters / $bytes_per_character );

	/*
	 * Translations
	 *  - There are fields to take all the plural forms (no known locale has more than 4 plural forms)
	 *  - Belongs to an original string
	 *  - Belongs to a user
	 *  - Status can be: new, approved, unapproved, current, spam or whatever you'd like
	 */
	$gp_schema['translations'] = "CREATE TABLE $wpdb->gp_translations (
		id int(10) NOT NULL auto_increment,
		original_id int(10) DEFAULT NULL,
		translation_set_id int(10) DEFAULT NULL,
		translation_0 text NOT NULL,
		translation_1 text DEFAULT NULL,
		translation_2 text DEFAULT NULL,
		translation_3 text DEFAULT NULL,
		translation_4 text DEFAULT NULL,
		translation_5 text DEFAULT NULL,
		user_id bigint(20) DEFAULT NULL,
		user_id_last_modified bigint(20) DEFAULT NULL,
		status varchar(20) NOT NULL default 'waiting',
		date_added datetime DEFAULT NULL,
		date_modified datetime DEFAULT NULL,
		warnings text DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY original_id (original_id),
		KEY user_id (user_id),
		KEY translation_set_id (translation_set_id),
		KEY translation_set_id_status (translation_set_id,status),
		KEY original_id_translation_set_id_status (original_id,translation_set_id,status),
		KEY date_added (date_added),
		KEY warnings (warnings(1))
	) $charset_collate;";

	/*
	 * Translations sets: A "translation set" holds all translated strings within a project for a specific locale.
	 * For example each WordPress Spanish translation (formal, informal and that of Diego) will be different sets.
	 * Most projects will have only one translation set per locale.
	 */

	/*
 	 * The maximum length for the slug component of the project_id_slug_locale key is limited by the index size limit
	 * minus the size of the project_id (4 bytes = 1 character) and locale (10 characters).
	 *
	 * Also make sure to never go over the length of the column.
	 */
	$max_pid_slug_locale_key_length = min( $max_index_length - 1 - 10, 255 );

	/*
 	 * The maximum length for the slug component of the locale_slug key is limited by the index size limit
	 * minus the size of the locale (10 characters).
	 *
	 * Also make sure to never go over the length of the column.
	 */
	$max_locale_slug_key_length = min( $max_index_length - 10, 255 );

	$gp_schema['translation_sets'] = "CREATE TABLE $wpdb->gp_translation_sets (
		id int(10) NOT NULL auto_increment,
		name varchar(255) NOT NULL,
		slug varchar(255) NOT NULL,
		project_id int(10) DEFAULT NULL,
		locale varchar(10) DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY project_id_slug_locale (project_id,slug({$max_pid_slug_locale_key_length}),locale),
		KEY locale_slug (locale,slug({$max_locale_slug_key_length}))
	) $charset_collate;";

	/*
	 * Original strings
	 *  - Has many translations
	 *  - Belongs to a project
	 *
	 * Note that 'references' is a reserved keyword in MySQL it *MUST* be surrounded in
	 * backticks during the creation of the table.  However during upgrades this will
	 * cause a warning to be created in the PHP error logs about incorrect SQL syntax.
	 *
	 * See https://core.trac.wordpress.org/ticket/20263 for more information.
	 *
	 */

	/*
 	 * The maximum length for the components of the singular_plural_context key is limited by the index size limit
	 * divided by three.
	 *
	 * Also make sure to never go over the length of the column (or in the case of a text type, a max of 255).
	 */
	$max_singular_plural_context_key_length = min( floor( $max_index_length / 3 ), 255 );

	$gp_schema['originals'] = "CREATE TABLE $wpdb->gp_originals (
		id int(10) NOT NULL auto_increment,
		project_id int(10) DEFAULT NULL,
		context varchar(255) DEFAULT NULL,
		singular text NOT NULL,
		plural text DEFAULT NULL,
		`references` text DEFAULT NULL,
		comment text DEFAULT NULL,
		status varchar(20) NOT NULL DEFAULT '+active',
		priority tinyint(4) NOT NULL DEFAULT 0,
		date_added datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY project_id_status (project_id,status),
		KEY singular_plural_context (singular({$max_singular_plural_context_key_length}),plural({$max_singular_plural_context_key_length}),context({$max_singular_plural_context_key_length})),
		KEY project_id_status_priority_date_added (project_id,status,priority,date_added)
	) $charset_collate;";

	/*
	 * Glossary Entries
	 */
	$gp_schema['glossary_entries'] = "CREATE TABLE $wpdb->gp_glossary_entries (
		id int(10) unsigned NOT NULL auto_increment,
		glossary_id int(10) unsigned NOT NULL,
		term varchar(255) NOT NULL,
		part_of_speech varchar(255) DEFAULT NULL,
		comment text DEFAULT NULL,
		translation varchar(255) DEFAULT NULL,
		date_modified datetime NOT NULL,
		last_edited_by bigint(20) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	/*
	 * Glossaries
	 */
	$gp_schema['glossaries'] = "CREATE TABLE $wpdb->gp_glossaries (
		id int(10) unsigned NOT NULL auto_increment,
		translation_set_id int(10)  NOT NULL,
		description text DEFAULT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	/*
	 * Projects
	 * - Has a project -- its parent
	 * - The path is the combination of the slugs of all its parents, separated by /
	 */
	$gp_schema['projects'] = "CREATE TABLE $wpdb->gp_projects (
		id int(10) NOT NULL auto_increment,
		name varchar(255) NOT NULL,
		slug varchar(255) NOT NULL,
		path varchar(255) NOT NULL,
		description text NOT NULL,
		parent_project_id int(10) DEFAULT NULL,
		source_url_template varchar(255) DEFAULT '',
		active tinyint(4) DEFAULT 0,
		PRIMARY KEY  (id),
		KEY path (path),
		KEY parent_project_id (parent_project_id)
	) $charset_collate;";

	/*
	 * Meta
	 */

	/*
 	 * The maximum length for the meta_key component of the object_type__meta_key key is limited by the index size limit
	 * minus the size of the object_type (32 characters).
	 *
	 * Also make sure to never go over the length of the column.
	 */
	$max_objtype_metakey_key_length = min( $max_index_length - 32, 255 );

	/*
 	 * The maximum length for the meta_key component of the object_type__object_id__meta_key key is limited by the index size limit
	 * minus the size of the object_type (32 characters) and the object_id (8 bytes = 2 characters).
	 *
	 * Also make sure to never go over the length of the column.
	 */
	$max_objtype_objid_metakey_key_length = min( $max_index_length - 32 - 2, 255 );

	$gp_schema['meta'] = "CREATE TABLE $wpdb->gp_meta (
		meta_id bigint(20) NOT NULL auto_increment,
		object_type varchar(32) NOT NULL default 'gp_option',
		object_id bigint(20) NOT NULL default 0,
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext DEFAULT NULL,
		PRIMARY KEY  (meta_id),
		KEY object_type__meta_key (object_type,meta_key({$max_objtype_metakey_key_length})),
		KEY object_type__object_id__meta_key (object_type,object_id,meta_key({$max_objtype_objid_metakey_key_length}))
	) $charset_collate;";

	/*
	 * Permissions
	 */
	$gp_schema['permissions'] = "CREATE TABLE $wpdb->gp_permissions (
		id int(10) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) DEFAULT NULL,
		action varchar(60) DEFAULT NULL,
		object_type varchar(255) DEFAULT NULL,
		object_id varchar(255) DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY user_id_action (user_id,action)
	) $charset_collate;";

	/**
	 * Filter the GlotPress database schema.
	 *
	 * @since 1.0.0
	 *
	 * @param array $gp_schema Schema definitions in SQL, table names without prefixes as keys.
	 */
	$gp_schema = apply_filters( 'gp_schema', $gp_schema );

	return $gp_schema;
}
