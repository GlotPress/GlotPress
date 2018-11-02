<?php
/**
 * Routes: GP_Route_Note class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 3.0.0
 */

/**
 * Class for all note related actions
 *
 * @since 3.0.0
 */
class GP_Route_Note extends GP_Route_Main {

	/**
	 * Processes the note set action to set on the translation.
	 *
	 * @since 3.0.0
	 *
	 * @return string $html The new row.
	 */
	public function new_post() {
		$translation_id = gp_post( 'translation_id' );
		$note           = gp_post( 'note' );
		$translation    = new GP_Translation( array( 'id' => $translation_id ) );

		if ( ! GP::$permission->current_user_can( 'approve', 'translation', $translation_id, array( 'translation' => $translation ) ) ) {
			return false;
		}

		if ( ! $this->verify_nonce( 'new-note-' . $translation_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$this->notices[] = __( 'The note was created!', 'glotpress' );
		$translation     = GP::$translation->get( $translation_id );
		$note_object     = GP::$notes->save();

		return $this->render_note( $note_object, $translation );
	}

	/**
	 * Processes the note set action to set on the translation.
	 *
	 * @since 3.0.0
	 *
	 * @return string $html The updated row.
	 */
	public function edit_post() {
		$translation_id = gp_post( 'translation_id' );
		$note           = gp_post( 'note' );
		$note_id        = gp_post( 'note_id' );
		$translation    = new GP_Translation( array( 'id' => $translation_id ) );

		if ( ! GP::$permission->current_user_can( 'approve', 'translation', $translation_id, array( 'translation' => $translation ) ) ) {
			return false;
		}

		if ( ! $this->verify_nonce( 'edit-note-' . $note_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$this->notices[] = __( 'The note was updated!', 'glotpress' );
		$translation     = GP::$translation->get( $translation_id );
		$note_object     = GP::$notes->edit( $note_id, $note, $translation );

		return $this->render_note( $note_object, $translation );
	}

	/**
	 * Processes the note set action to set on the translation.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function delete_post() {
		$note_id = gp_post( 'note_id' );

		if ( ! $this->verify_nonce( 'delete-note-' . $note_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$this->notices[] = __( 'The note was deleted!', 'glotpress' );
		GP::$notes->delete_all( array( 'id' => $note_id ) );

		return true;
	}

	/**
	 * Render the note
	 *
	 * @param object $note        Note object.
	 * @param object $translation Translation object.
	 */
	private function render_note( $note, $translation ) {
		require_once GP_TMPL_PATH . 'helper-functions.php';
		$can_approve = $this->can( 'approve', 'translation-set', $translation->translation_set_id );
		return gp_render_note( $note, $can_approve );
	}
}
