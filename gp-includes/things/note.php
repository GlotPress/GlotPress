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
		'date_modified',
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
	public $non_updatable_attributes = array( 'id' );
	
	public $default_order = 'ORDER BY date_added DESC';

	/**
	 * Save the note
	 *
	 * @since 3.0.0
	 *
	 * @param string $note    The new note.
	 * @param object $translation The translation object.
	 *
	 * @return object The output of the query.
	 */
	public function save( $args = null ) {
		global $wpdb;
		if ( ! GP::$permission->current_user_can(
			'approve', 'translation', $translation->id, array(
				'translation' => $translation,
			)
		) ) {
			return false;
		}
		
		$note = trim($note);
		
		return $this->create(
			array(
				'original_id' => $translation->original_id,
				'translation_set_id' => $translation->translation_set_id,
				'note' => $note,
				'user_id' => get_current_user_id(), 
			)
		);
	}
	
	public function edit($note_id, $note, $translation)
	{
		if ( false === GP::$permission->current_user_can( 'admin', 'notes', $translation->id ) ) {
			return false;
		}
		
		$this->update(array('note' => $note), array('id' => $note_id));
		
		return $this->get($note_id);
	}

	/**
	 * Retrieves the note for this entry.
	 *
	 * @since 3.0.0
	 *
	 * @param object $entry The translation entry.
	 *
	 * @return array notes
	 */
	function get_by_entry( $entry, $order = null ) {
		return $this->many( 
			$this->select_all_from_conditions_and_order( 
				array(
					'original_id' => $entry->original_id,
					'translation_set_id' => $entry->translation_set_id,
				), 
				$order 
			) 
		);
	}
}

GP::$notes = new GP_Note();
