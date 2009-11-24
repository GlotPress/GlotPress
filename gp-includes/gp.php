<?php
class GP {
	// models
	static $project;
	static $user;
	static $translation_set;
	static $permission;
	static $translation;
	static $original;
	// other singletons
	static $router;
	static $redirect_notices = array();
	static $translation_warnings;
	// plugins can use this space
	static $vars = array();
}