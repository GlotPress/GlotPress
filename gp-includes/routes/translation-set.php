<?php
/**
 * Routes: GP_Route_Translation_Set class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Core class used to implement the translation set route.
 *
 * @since 1.0.0
 */
class GP_Route_Translation_Set extends GP_Route_Main {
	public function new_get() {
		$set = new GP_Translation_Set;
		$set->project_id = gp_get( 'project_id' );
		$project = $set->project_id? GP::$project->get( $set->project_id ) : null;
		if ( $this->cannot_edit_set_and_redirect( $set ) ) return;
		$this->tmpl( 'translation-set-new', get_defined_vars() );
	}

	public function new_post() {
		if ( $this->invalid_nonce_and_redirect( 'add-translation-set' ) ) {
			return;
		}

		$new_set = new GP_Translation_Set( gp_post( 'set', array() ) );
		if ( $this->cannot_edit_set_and_redirect( $new_set ) ) return;
		if ( $this->invalid_and_redirect( $new_set ) ) return;
		$created_set = GP::$translation_set->create_and_select( $new_set );
		if ( !$created_set ) {
			$this->errors[] = __( 'Error in creating translation set!', 'glotpress' );
			$this->redirect( gp_url( '/sets/-new', array( 'project_id' => $new_set->project_id ) ) );
		} else {
			GP::$project->get( $created_set->project_id );
			$this->notices[] = __( 'The translation set was created!', 'glotpress' );
			$this->redirect( gp_url_project_locale( GP::$project->get( $created_set->project_id ), $created_set->locale, $created_set->slug ) );
		}
	}

	public function single( $set_id ) {
		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );
		if ( !$items) return;
		list( $set, $project, ) = $items;
		$this->redirect( gp_url_project( $project, array( $set->locale, $set->slug ) ) );
	}

	public function edit_get( $set_id ) {
		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );
		if ( !$items ) return;
		list( $set, $project, $locale ) = $items;
		if ( $this->cannot_and_redirect( 'write', 'project', $set->project_id, gp_url_project( $project ) ) ) return;
		$url = gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) );
		$this->tmpl( 'translation-set-edit', get_defined_vars() );
	}

	/**
	 * Saves settings for a translation set and redirects back to the project locales page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $set_id A translation set id to edit the settings of.
	 */
	public function edit_post( $set_id ) {
		if ( $this->invalid_nonce_and_redirect( 'edit-translation-set_' . $set_id ) ) {
			return;
		}

		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );

		if ( ! $items ) {
			return;
		}

		list( $set, ,  ) = $items;

		$new_set = new GP_Translation_Set( gp_post( 'set', array() ) );

		if ( $this->cannot_edit_set_and_redirect( $new_set ) ) {
			return;
		}

		if ( $this->invalid_and_redirect( $new_set, gp_url( '/sets/' . $set_id . '/-edit' ) ) ) {
			return;
		}

		if ( ! $set->update( $new_set ) ) {
			$this->errors[] = __( 'Error in updating translation set!', 'glotpress' );
			$this->redirect();
			return;
		}

		$project = GP::$project->get( $new_set->project_id );

		$this->notices[] = __( 'The translation set was updated!', 'glotpress' );

		$this->redirect( gp_url_project_locale( $project, $new_set->locale, $new_set->slug ) );
	}

	/**
	 * Deletes a translation set.
	 *
	 * @since 2.0.0
	 *
	 * @param int $set_id The id of the translation set to delete.
	 */
	public function delete_post( $set_id ) {
		if ( $this->invalid_nonce_and_redirect( 'delete-translation-set_' . $set_id ) ) {
			return;
		}

		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );
		if ( ! $items ) {
			return;
		}

		list( $set, $project, $locale ) = $items;
		if ( $this->cannot_and_redirect( 'delete', 'project', $set->project_id, gp_url_project( $project ) ) ) {
			return;
		}

		if ( ! $set->delete() ) {
			$this->errors[] = __( 'Error deleting translation set!', 'glotpress' );
			$this->redirect();
			return;
		}

		$this->notices[] = __( 'The translation set was deleted!', 'glotpress' );
		$this->redirect( gp_url_project( $project ) );
	}

	/**
	 * Displays the delete page for translations sets.
	 *
	 * @since 2.0.0
	 *
	 * @param int $set_id The id of the translation set to delete.
	 */
	public function delete_get( $set_id ) {
		$items = $this->get_set_project_and_locale_from_set_id_or_404( $set_id );
		if ( ! $items ) {
			return;
		}

		list( $set, $project, $locale ) = $items;
		if ( $this->cannot_and_redirect( 'delete', 'project', $set->project_id, gp_url_project( $project ) ) ) {
			return;
		}

		$url = gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) );
		$this->tmpl( 'translation-set-delete', get_defined_vars() );
	}

	/**
	 * Determines whether the current user can edit a translation set.
	 *
	 * @param GP_Translation_Set $set The translation set to edit.
	 */
	private function cannot_edit_set_and_redirect( $set ) {
		return $this->cannot_and_redirect( 'write', 'project', $set->project_id );
	}

	private function get_set_project_and_locale_from_set_id_or_404( $set_id ) {
		$set = GP::$translation_set->get( $set_id );
		if ( !$set ) {
			$this->die_with_404( array( 'title' => "Translation set wasn't found" ) );
			return;
		}
		$project =  GP::$project->get( $set->project_id );
		if ( !$project ) {
			$this->die_with_404( array( 'title' => "The project associated with this translation set wasn't found" ) );
			return;
		}
		$locale = $locale = GP_Locales::by_slug( $set->locale );
		if ( !$locale ) {
			$this->die_with_404( array( 'title' => "The locale associated with this translation set wasn't found" ) );
			return;
		}
		return array( $set, $project, $locale );
	}
}
