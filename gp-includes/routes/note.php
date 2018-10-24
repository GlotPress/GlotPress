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
		$status         = gp_post( 'original_id' );
		$translation_id = gp_post( 'translation_id' );
		$note = gp_post( 'note' );

		if ( ! $this->verify_nonce( 'new-note-' . $translation_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$translation = GP::$translation->get( $translation_id );
		$noteObject = GP::$notes->save( $note, $translation );

		$this->render_note($noteObject, $translation);
		return true;
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
		$note = gp_post( 'note' );
		$note_id = gp_post( 'note_id' );

		if ( ! $this->verify_nonce( 'edit-note-' . $note_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$translation = GP::$translation->get( $translation_id );
		$noteObject = GP::$notes->edit( $note_id, $note, $translation );

		$this->render_note( $noteObject, $translation );
		return true;
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

		GP::$notes->delete_all( array('id' => $note_id) );

		return true;
	}

	/**
	 *
	 * @param object $note
	 * @param object $translation
	 */
	private function render_note( $note, $translation )	{
		require_once GP_TMPL_PATH . 'helper-functions.php';
		$can_approve = $this->can( 'approve', 'translation-set', $translation->translation_set_id );
		render_note($note, $can_approve);
	}
}
