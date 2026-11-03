<?php
/**
 * Route exit exception.
 *
 * @package GlotPress
 */

/**
 * Exception thrown by GP_Route::exit_() while a faked request is running, so a
 * route stops at the point where a real request would have exited instead of
 * continuing to run the code that follows.
 */
class GP_Route_Exit_Exception extends Exception {}
