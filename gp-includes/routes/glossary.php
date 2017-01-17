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
		if ( $this->invalid_nonce_and_redirect( 'add-glossary' ) ) {
			return;
		}

		$new_glossary    = new GP_Glossary( gp_post( 'glossary' ) );
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

			$this->redirect( $created_glossary->path() );
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
		if ( $this->invalid_nonce_and_redirect( 'edit-glossary_' . $glossary_id ) ) {
			return;
		}

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

		$this->redirect( $glossary->path() );
	}

	/**
	 * Displays the delete page for glossaries.
	 *
	 * @since 2.0.0
	 *
	 * @param int $glossary_id The id of the glossary to delete.
	 */
	public function delete_get( $glossary_id ) {
		$glossary = GP::$glossary->get( $glossary_id );

		if ( ! $glossary ) {
			$this->redirect_with_error( __( 'Cannot find glossary.', 'glotpress' ) );
		}

		if ( $this->cannot_delete_glossary_and_redirect( $glossary ) ) {
			return;
		}

		$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
		$locale          = GP_Locales::by_slug( $translation_set->locale );
		$project         = GP::$project->get( $translation_set->project_id );

		$this->tmpl( 'glossary-delete', get_defined_vars() );
	}

	/**
	 * Delete a glossary.
	 *
	 * @since 2.0.0
	 *
	 * @param int $glossary_id The id of the glossary to delete.
	 */
	public function delete_post( $glossary_id ) {
		if ( $this->invalid_nonce_and_redirect( 'delete-glossary_' . $glossary_id ) ) {
			return;
		}

		$glossary = GP::$glossary->get( $glossary_id );

		if ( $this->cannot_delete_glossary_and_redirect( $glossary ) ) {
			return;
		}

		$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
		$project         = GP::$project->get( $translation_set->project_id );

		if ( ! $glossary->delete() ) {
			$this->errors[] = __( 'Error deleting glossary!', 'glotpress' );
			$this->redirect();
			return;
		}

		$this->notices[] = __( 'The glossary was deleted!', 'glotpress' );

		$this->redirect( gp_url_join( gp_url_project( $project ), array( $translation_set->locale, $translation_set->slug ) ) );
	}

	/**
	 * Checks to see if the current user can edit a glossary or not.  If they cannot it redirects back to the project page.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Glossary $glossary The glossary object to check.
	 *
	 * @return bool
	 */
	private function cannot_edit_glossary_and_redirect( $glossary ) {
		return $this->cannot_and_redirect( 'approve', 'translation-set', $glossary->translation_set_id );
	}

	/**
	 * Checks to see if the current user can delete a glossary or not.  If they cannot it redirects back to the project page.
	 *
	 * @since 2.0.0
	 *
	 * @param GP_Glossary $glossary The glossary object to check.
	 *
	 * @return bool
	 */
	private function cannot_delete_glossary_and_redirect( $glossary ) {
		return $this->cannot_and_redirect( 'delete', 'translation-set', $glossary->translation_set_id );
	}

}
