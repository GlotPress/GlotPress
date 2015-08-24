<?php
/**
 * @package GlotPress
 */
/*
Plugin Name: GlotPress
Plugin URI: http://glotpress.org/
Description: Translation app.
Version: 0.1
Author: deliciousbrains
Author URI: http://deliciousbrains.com
License: GPLv2 or later
Text Domain: glotpress
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define( 'GP_VERSION', '0.1' );
define( 'GP_ROUTING', true );
define( 'GP_PLUGIN_FILE', __FILE__ );
define( 'GP_PATH', dirname( __FILE__ ) . '/' );
define( 'GP_INC', 'gp-includes/' );

require_once GP_PATH . 'gp-settings.php';
