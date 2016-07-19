<?php
/**
 * Routes: GP_Route_Index class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Core class used to implement the index route.
 *
 * @since 1.0.0
 */
class GP_Route_Index extends GP_Route_Main {
	public function index() {
		$this->redirect( gp_url_project( '' ) );
	}
}
