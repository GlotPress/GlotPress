<?php
function gp_logged_in() {
	global $wp_auth_object;
	return (bool)$wp_auth_object->get_current_user();
}

function gp_current_user() {
	global $wp_auth_object;
	return $wp_auth_object->get_current_user();
}
?>