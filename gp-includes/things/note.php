<?php
/**
 * Things: GP_Note class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 3.0.0
 */

/**
 * Core class used to implement the notes system.
 *
 * @since 3.0.0
 */
class GP_Note extends GP_Thing {

	/**
	 * Name of the database table.
	 *
	 * @var string $table_basename
	 */
	public $table_basename = 'gp_notes';

	/**
	 * List of field names for a translation.
	 *
	 * @var array $field_names
	 */
	public $field_names = array(
		'id',
		'original_id',
		'translation_set_id',
		'note',
		'user_id',
		'date_added',
	);

	/**
	 * List of field names which have an integer value.
	 *
	 * @var array $int_fields
	 */
	public $int_fields = array(
		'id',
		'original_id',
		'translation_set_id',
		'user_id',
	);

	/**
	 * List of field names which cannot be updated.
	 *
	 * @var array $non_updatable_attributes
	 */
	public $non_updatable_attributes = array( 'id', 'original_id', 'translation_set_id' );


	/**
	 * Save the note
	 *
	 * @since 3.0.0
	 *
	 * @param string $note    The new note.
	 * @param string $translation The translation object.
	 *
	 * @return object The output of the query.
	 */
	function save( $note, $translation ) {
		global $wpdb;
		if ( ! GP::$permission->current_user_can(
			'approve', 'translation', $translation->id, array(
				'translation' => $translation,
			)
		) ) {
			return false;
		}
		if ( GP::$permission->current_user_can( 'admin', 'notes', $translation->id ) ) {
			$saved_note = esc_html( gp_post( 'update_note' ) );
		}
		if ( empty( $saved_note ) ) {
			$saved_note = $this->get( $translation );
			$saved_note = $saved_note['note'];
		}

		$current_date = $translation->now_in_mysql_format();
		$current_user = wp_get_current_user();
		$current_user = $current_user->data->display_name;

		if ( ! empty( $saved_note ) ) {
			if ( ! empty( $note ) ) {
					$note = $current_user . ' @ ' . $current_date . ' added:' . "\n" . $note . "\n" . $saved_note;
			} else {
					$note = $saved_note;
			}
			return $translation->query(
				"UPDATE $wpdb->gp_notes SET note = '%s', user_id = '%s', date_added = '%s' WHERE (original_id = '%s' AND translation_set_id = '%s')", $note, get_current_user_id(), $current_date, $translation->original_id, $translation->translation_set_id
			);
		} else {
			if ( ! empty( $note ) ) {
				$note = $current_user . ' @ ' . $current_date . ' added:' . "\n" . $note;
				return $translation->query(
					"
					INSERT INTO $wpdb->gp_notes (
					original_id, translation_set_id, note, user_id, date_added
					)
					VALUES (%s, %s, %s, %s, %s)", $translation->original_id, $translation->translation_set_id, $note, get_current_user_id(), $current_date
				);
			}
		}
	}


	/**
	 * Retrieves the note for this entry.
	 *
	 * @since 3.0.0
	 *
	 * @param string $entry The translation entry.
	 *
	 * @return string The note.
	 */
	function get( $entry ) {
		global $wpdb;

		$result = $wpdb->get_results( sprintf( "SELECT * FROM $wpdb->gp_notes WHERE (original_id = '%s' AND translation_set_id = '%s') LIMIT 1", $entry->original_id, $entry->translation_set_id ), ARRAY_A );
		if ( count( $result ) > 0 ) {
			return $result[0];
		}
		return array(
			'note' => '',
		);
	}
}

GP::$notes = new GP_Note();
