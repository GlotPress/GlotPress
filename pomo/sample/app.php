<?php

require_once '../translations.php';
require_once '../mo.php';


function __($text) {
	global $translations;
	return $translations->translate($text);
}

function _e($text) {
	global $translations;
	echo $translations->translate($text);
}

function __n($singular, $plural, $count) {
	global $translations;
	return $translations->translate_plural($singular, $plural, $count);
}

function &get_translations($locale) {
	$mo_filename = "languages/$locale.mo";
	if (is_readable($mo_filename)) {
		$translations = new MO();
		$translations->import_from_file($mo_filename);
	} else {
		$translations = new Translations();
	}
	return $translations;
}

// get the locale from somewhere: subomain, config, GET var, etc.
// it can be safely empty
$locale = 'bg';

// load the translations
$translations = &get_translations($locale);

//here comes the real app
$user = 'apok';
$messages = rand(0, 2);

printf(__('Welcome %s!')."\n", $user);

printf(__n('You have one new message.', 'You have %s new messages.', $messages)."\n", $messages);

_e("Bye\n");
?>
