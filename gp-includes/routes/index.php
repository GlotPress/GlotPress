<?php
class GP_Route_Index extends GP_Route_Main {
	public function index() {
		$this->redirect( gp_url_project( '' ) );
	}
}