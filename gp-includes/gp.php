<?php
class GP {
	// models
	public static $project;
	public static $translation_set;
	public static $permission;
	public static $validator_permission;
	public static $translation;
	public static $original;
	public static $glossary;
	public static $glossary_entry;

	// other singletons
	public static $router;
	public static $redirect_notices = array();
	public static $translation_warnings;
	public static $builtin_translation_warnings;
	public static $current_route = null;
	public static $formats;

	// Object cache placeholders
	public static $cache_one = array();
	public static $cache_many = array();
	public static $cache_value = array();
	
}
