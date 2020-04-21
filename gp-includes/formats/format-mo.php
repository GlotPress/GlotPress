<?php
/**
 * GlotPress Format MO class.
 *
 * @since 2.3.1
 *
 * @package GlotPress
 */

/**
 * Format class used to support MO file format.
 *
 * @since 2.3.1
 */
class GP_Format_MO extends GP_Format_PO {

	public $name           = 'Machine Object Message Catalog (.mo)';
	public $extension      = 'mo';
	public $alt_extensions = array();

	public $class = 'MO';

	/**
	 * Override the comments function as PO files do not use it.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Format $format The format object to set the header for.
	 * @param string    $text   The text to add to the comment.
	 */
	protected function add_comments_before_headers( $format, $text ) {
		return;
	}
}

GP::$formats['mo'] = new GP_Format_MO();
