<?php
/**
 * Routes: GP_Route_Glossary class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Core class used to implement the glossary route.
 *
 * @since 1.0.0
 */
class GP_Route_Glossary extends GP_Route_Main {

	public function new_get() {
		$glossary = new GP_Glossary;
		$glossary->translation_set_id = gp_get( 'translation_set_id' );

		$translation_set = $glossary->translation_set_id ? GP::$translation_set->get( $glossary->translation_set_id ) : null;

		if ( ! $translation_set ) {
			$this->redirect_with_error( __( 'Couldn&#8217;t find translation set with this ID.', 'glotpress' ) );
			return;
		}

		$project = GP::$project->get( $translation_set->project_id );

		if ( GP::$glossary->by_set_id( $glossary->translation_set_id ) ) {
			$glossary_url = gp_url_join( gp_url_project( $project, array( $translation_set->locale, $translation_set->slug ) ), array('glossary') );
			$this->redirect_with_error( __( 'The glossary for this translation set already exists.', 'glotpress' ), $glossary_url );
			return;
		}

		if ( $this->cannot_edit_glossary_and_redirect( $glossary ) ) {
			return;
		}

		$locale = GP_Locales::by_slug( $translation_set->locale );

		$this->tmpl( 'glossary-new', get_defined_vars() );
	}

	public function new_post() {
		$new_glossary    = new GP_Glossary( gp_post('glossary') );
		$translation_set = $new_glossary->translation_set_id ? GP::$translation_set->get( $new_glossary->translation_set_id ) : null;

		if ( ! $translation_set ) {
			$this->redirect_with_error( __( 'Couldn&#8217;t find translation set with this ID.', 'glotpress' ), gp_url( '/glossaries/-new', array( 'translation_set_id' => $new_glossary->translation_set_id ) ) );
			return;
		}

		if ( GP::$glossary->by_set_id( $new_glossary->translation_set_id ) ) {
			$this->redirect_with_error( __( 'The glossary for this translation set already exists.', 'glotpress' ), gp_url( '/glossaries/-new', array( 'translation_set_id' => $new_glossary->translation_set_id ) ) );
			return;
		}

		if ( $this->cannot_edit_glossary_and_redirect( $new_glossary ) ) {
			return;
		}

		$created_glossary = GP::$glossary->create_and_select( $new_glossary );

		if ( $created_glossary ) {
			$this->notices[] = __( 'The glossary was created!', 'glotpress' );
			$set_project     = GP::$project->get( $translation_set->project_id );

			$this->redirect( gp_url_join( gp_url_project( $set_project, array( $translation_set->locale, $translation_set->slug ) ), array('glossary') ) );
		}
		else {
			$this->errors[] = __( 'Error in creating glossary!', 'glotpress' );
			$this->redirect( gp_url( '/glossaries/-new', array( 'translation_set_id' => $new_glossary->translation_set_id ) ) );
		}
	}

	public function edit_get( $glossary_id ) {
		$glossary = GP::$glossary->get( $glossary_id );

		if ( ! $glossary ) {
			$this->redirect_with_error( __( 'Cannot find glossary.', 'glotpress' ) );
		}

		$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
		$locale          = GP_Locales::by_slug( $translation_set->locale );
		$project         = GP::$project->get( $translation_set->project_id );

		$this->tmpl( 'glossary-edit', get_defined_vars() );
	}

	public function edit_post( $glossary_id ) {
		$glossary     = GP::$glossary->get( $glossary_id );
		$new_glossary = new GP_Glossary( gp_post('glossary') );

		if ( $this->cannot_edit_glossary_and_redirect( $glossary ) ) {
			return;
		}

		if ( ! $glossary->update( $new_glossary ) ) {
			$this->errors[] = __( 'Error in updating glossary!', 'glotpress' );
			$this->redirect();
			return;
		}

		$this->notices[] = __( 'The glossary was updated!', 'glotpress' );
		$translation_set = $new_glossary->translation_set_id ? GP::$translation_set->get( $new_glossary->translation_set_id ) : null;
		$set_project     = GP::$project->get( $translation_set->project_id );

		$this->redirect( gp_url_join( gp_url_project( $set_project, array( $translation_set->locale, $translation_set->slug ) ), array('glossary') ) );
	}

	private function cannot_edit_glossary_and_redirect( $glossary ) {
		return $this->cannot_and_redirect( 'approve', 'translation-set', $glossary->translation_set_id );
	}

}
