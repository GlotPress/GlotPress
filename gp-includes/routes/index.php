<?php
class GP_Route_Index extends GP_Route_Main {
	function index() {
		wp_redirect( gp_url_project( '' ) );
	}
}