<?php
class GP_Route_Translation_Set extends GP_Route_Main {
	function new_get() {
		$set = new GP_Translation_Set;
		$set->project_id = gp_get( 'project_id' );
		$project = $set->project_id? GP::$project->get( $set->project_id ) : null; 
		if ( $this->cannot_edit_set_and_redirect( $set ) ) return;
		$this->tmpl( 'translation-set-new', get_defined_vars() );
	}
	
	function new_post() {
		$new_set = new GP_Translation_Set( gp_post( 'set', array() ) );
		if ( $this->cannot_edit_set_and_redirect( $new_set ) ) return;
		if ( $this->invalid_and_redirect( $new_set ) ) return;
		$created_set = GP::$translation_set->create_and_select( $new_set );
		if ( $created_set ) $project = GP::$project->get( $created_set->project_id );
		if ( !$created_set ) {
			$this->errors[] = __('Error in creating translation set!');
			$this->redirect( gp_url( '/sets/-new', array( 'project_id' => $new_set->project_id ) ) );
		} else {
			$this->notices[] = __('The translation set was created!');
			$this->redirect( gp_url_project_locale( GP::$project->get( $created_set->project_id ), $created_set->locale, $created_set->slug ) );
		}
	}
	
	function single( $set_id ) {
		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );
		if ( !$items) return;
		list( $set, $project, $locale ) = $items;
		$this->redirect( gp_url_project( $project, array( $set->locale, $set->slug ) ) );
	}
	
	function edit_get( $set_id ) {
		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );
		if ( !$items ) return;
		list( $set, $project, $locale ) = $items;
		if ( $this->cannot_and_redirect( 'write', 'project', $set->project_id, gp_url_project( $project ) ) ) return;
		$url = gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) );
		$this->tmpl( 'translation-set-edit', get_defined_vars() );
	}
	
	function edit_post( $set_id ) {
		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );
		if ( !$items ) return;
		list( $set, $project, $locale ) = $items;
		$new_set = new GP_Translation_Set( gp_post( 'set', array() ) );
		if ( $this->cannot_edit_set_and_redirect( $new_set ) ) return;
		if ( $this->invalid_and_redirect( $new_set, gp_url( '/sets/-new' ) ) ) return;
		if ( !$set->update( $new_set ) ) {
			$this->errors[] = __('Error in updating translation set!');
			$this->redirect();
			return;
		}
		$project = GP::$project->get( $new_set->project_id );
		$this->notices[] = __( 'The translation set was updated!' );
		$this->redirect( gp_url_project_locale( $project, $new_set->locale, $new_set->slug ) );
	}

	private function cannot_edit_set_and_redirect( $set ) {
		return $this->cannot_and_redirect( 'write', 'project', $set->project_id );
	}
		
	private function get_set_project_and_locale_from_set_id_or_404( $set_id ) {
		$set = GP::$translation_set->get( $set_id );
		if ( !$set ) {
			$this->tmpl_404( array( 'title' => "Translation set wasn't found" ) );
			return;
		}
		$project =  GP::$project->get( $set->project_id );
		if ( !$project ) {
			$this->tmpl_404( array( 'title' => "The project associated with this translation set wasn't found" ) );
			return;
		}
		$locale = $locale = GP_Locales::by_slug( $set->locale );
		if ( !$locale ) {
			$this->tmpl_404( array( 'title' => "The locale associated with this translation set wasn't found" ) );
			return;
		}
		return array( $set, $project, $locale );
	}
}