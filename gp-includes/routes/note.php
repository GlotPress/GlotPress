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
		$translation    = GP::$translation->get( $translation_id );
		$admin          = GP::$permission->current_user_can( 'approve', 'translation', $translation_id, array( 'translation' => $translation ) );

		if ( get_current_user_id() !== (int) $translation->user_id && ! $admin ) {
			return $this->die_with_error( __( 'Sorry, you are not allowed to create this note.', 'glotpress' ), 403 );
		}

		if ( ! $this->verify_nonce( 'new-note-' . $translation_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$this->notices[] = __( 'The note was created!', 'glotpress' );
		$note            = GP::$notes->save();

		$this->tmpl( 'note', get_defined_vars() );

		return $note;
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
		$translation    = GP::$translation->get( $translation_id );
		$note_object    = GP::$notes->get( $note_id );
		$admin          = GP::$permission->current_user_can( 'approve', 'translation', $translation_id, array( 'translation' => $translation ) );

		if ( get_current_user_id() !== (int) $note_object->user_id && ! $admin ) {
			return $this->die_with_error( __( 'Sorry, you are not allowed to edit this note.', 'glotpress' ), 403 );
		}

		if ( ! $this->verify_nonce( 'edit-note-' . $note_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$this->notices[] = __( 'The note was updated!', 'glotpress' );
		$translation     = GP::$translation->get( $translation_id );
		$note            = GP::$notes->edit( $note_id, wp_kses( $note, [] ), $translation );

		$this->tmpl( 'note', get_defined_vars() );
	}

	/**
	 * Processes the note set action to set on the translation.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function delete_post() {
		$translation_id = gp_post( 'translation_id' );
		$note_id        = gp_post( 'note_id' );
		$translation    = new GP_Translation( array( 'id' => $translation_id ) );
		$note_object    = GP::$notes->get( $note_id );
		$admin          = GP::$permission->current_user_can( 'approve', 'translation', $translation_id, array( 'translation' => $translation ) );

		if ( get_current_user_id() !== (int) $note_object->user_id && ! $admin ) {
			return $this->die_with_error( __( 'Sorry, you are not allowed to delete this note.', 'glotpress' ), 403 );
		}

		if ( ! $this->verify_nonce( 'delete-note-' . $note_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$this->notices[] = __( 'The note was deleted!', 'glotpress' );
		GP::$notes->delete_all( array( 'id' => $note_id ) );

		return true;
	}
}
