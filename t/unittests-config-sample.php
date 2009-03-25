<?php
// ** MySQL settings - You can get this info from your web host ** //

/** The name of the test database for GlotPress */
define('GPDB_NAME', 'glotpress_test');

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
 * GlotPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$gp_table_prefix = 'gp_';

?>
