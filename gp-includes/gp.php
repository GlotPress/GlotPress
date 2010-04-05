<?php
class GP {
	// models
	static $project;
	static $user;
	static $translation_set;
	static $permission;
	static $validator_permission;
	static $translation;
	static $original;
	// other singletons
	static $router;
	static $redirect_notices = array();
	static $translation_warnings;
	static $builtin_translation_warnings;
	static $current_route = null;
	static $formats;
	// plugins can use this space
	static $vars = array();
	// for plugin singletons
	static $plugins;
}
GP::$plugins = new stdClass();