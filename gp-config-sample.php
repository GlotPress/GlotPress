<?php
/**
 *	The base configuration of the GlotPress
 *
 *	This file has the following configurations: MySQL settings, table prefix,
 *	secret keys, GlotPress language and integration with WordPress user system.
 *	You can get the MySQL settings from your web host.
 *
 */

/** The name of the database for GlotPress */
define('GPDB_NAME', 'glotpress');

/** MySQL database username */
define('GPDB_USER', 'username');

/** MySQL database password */
define('GPDB_PASSWORD', 'password');

/** MySQL hostname */
define('GPDB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('GPDB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('GPDB_COLLATE', '');

/**#@+
 * Authentication Unique Keys.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/ WordPress.org secret-key service}
 *
 * @since 0.1
 */
define('GP_AUTH_KEY', 'put your unique phrase here');
define('GP_SECURE_AUTH_KEY', 'put your unique phrase here');
define('GP_LOGGED_IN_KEY', 'put your unique phrase here');
define('GP_NONCE_KEY', 'put your unique phrase here');
/**#@-*/

/**
 * GlotPress Localized Language, defaults to English.
 *
 * Change this to localize GlotPress. A corresponding MO file for the chosen
 * language must be installed to languages/. For example, install
 * fr_FR.mo to languages/ and set GP_LANG to 'fr_FR' to enable French
 * language support.
 */
define('GP_LANG', '');

/**
 * Custom users and usermeta tables for integration with WordPress user system.
 * 
 * You might want to delete your current permissions, since they will point to different
 * users in the custom table. You can use `php scripts/wipe-permissions.php` for that.
 * 
 * If you start with fresh permissions, you can add admins via `php scripts/add-admin.php`
 */
// define('CUSTOM_USER_TABLE', 'wp_users');
// define('CUSTOM_USER_META_TABLE', 'wp_usermeta');

/**
 * GlotPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$gp_table_prefix = 'gp_';