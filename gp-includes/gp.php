<?php
/**
 * GP class
 *
 * @package GlotPress
 * @since 1.0.0
 */

/**
 * Core class used to store singleton instances.
 *
 * @since 1.0.0
 */
class GP {

	/**
	 * Model for project.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Project
	 */
	public static $project;

	/**
	 * Model for transalation set.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Translation_Set
	 */
	public static $translation_set;

	/**
	 * Model for permission.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Permission
	 */
	public static $permission;

	/**
	 * Model for validator permission.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Validator_Permission
	 */
	public static $validator_permission;

	/**
	 * Model for administrator permission.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Administrator_Permission
	 */
	public static $administrator_permission;

	/**
	 * Model for translation.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Translation
	 */
	public static $translation;

	/**
	 * Model for original.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Original
	 */
	public static $original;

	/**
	 * Model for glossary.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Glossary
	 */
	public static $glossary;

	/**
	 * Model for glossary entry.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Glossary_Entry
	 */
	public static $glossary_entry;

	/**
	 * Singleton for router.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Router
	 */
	public static $router;

	/**
	 * Singleton for translation warnings.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Translation_Warnings
	 */
	public static $translation_warnings;

	/**
	 * Singleton for built-in translation warnings.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Builtin_Translation_Warnings
	 */
	public static $builtin_translation_warnings;

	/**
	 * Array of notices.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public static $redirect_notices = array();

	/**
	 * Holds the current route.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	public static $current_route = null;

	/**
	 * Array of available formats.
	 *
	 * @since 1.0.0
	 *
	 * @var GP_Format[]
	 */
	public static $formats;

	/**
	 * Array of enqueued style sheets.
	 *
	 * @since 2.2.0
	 *
	 * @var array
	 */
	public static $styles;

	/**
	 * Array of enqueued scripts.
	 *
	 * @since 2.2.0
	 *
	 * @var array
	 */
	public static $scripts;
}
