<?php
class GP_Route_Translation_Set extends GP_Route_Main {
	function new_get() {
		// TODO: check permissions for project	
		$set = new GP_Translation_Set();
		$set->project_id = gp_get( 'project_id' );
		$all_project_options = self::_options_from_projects( GP::$project->all() );
		$all_locale_options = self::_options_from_locales( GP_Locales::locales() );
		gp_tmpl_load( 'translation-set-new', get_defined_vars() );
	}
	
	function new_post() {
		// TODO: check permissions for project and parent project
		$set = GP::$translation_set->create_and_select( gp_post( 'set' ) );
		$project = GP::$project->get( $set->project_id );
		if ( !$set ) {
			$set = new GP_Translation_Set();
			gp_notice_set( __('Error in creating translation set!'), 'error' );
			wp_redirect( gp_url('/sets/_new') );
		} else {
			gp_notice_set( __('The translation set was created!') );
			wp_redirect( gp_url_project_locale( $project, $set->locale, $set->slug ) );
		}
	}
	
}