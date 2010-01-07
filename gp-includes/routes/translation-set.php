<?php
class GP_Route_Translation_Set extends GP_Route_Main {
	function new_get() {
		$set = new GP_Translation_Set();
		$set->project_id = gp_get( 'project_id' );
		if ( $set->project_id ) {
			$this->can_or_redirect( 'write', 'project', $set->project_id, gp_url_project( GP::$project->get( $set->project_id ) ) );
		}
		$all_project_options = self::_options_from_projects( GP::$project->all() );
		$all_locale_options = self::_options_from_locales( GP_Locales::locales() );
		gp_tmpl_load( 'translation-set-new', get_defined_vars() );
	}
	
	function new_post() {
		$new_set = new GP_Translation_Set( gp_post( 'set' ) );
		if ( $new_set->project_id ) {
			$this->can_or_redirect( 'write', 'project', $new_set->project_id,
					gp_url_project( GP::$project->get( $new_set->project_id ) ) );
		} else {
			$this->can_or_redirect( 'write', 'project', null, gp_url_project( '' ) );
		}
		$this->validate_or_redirect( $new_set, gp_url( '/sets/_new', array( 'project_id' => $new_set->project_id ) ) );
		$set = GP::$translation_set->create_and_select( $new_set );
		if ( $set ) $project = GP::$project->get( $set->project_id );
		if ( !$set ) {
			$this->errors[] = __('Error in creating translation set!');
			gp_redirect( gp_url( '/sets/_new', array( 'project_id' => $new_set->project_id ) ) );
		} else {
			$this->notices[] = __('The translation set was created!');
			gp_redirect( gp_url_project_locale( $project, $set->locale, $set->slug ) );
		}
	}
	
}