<?php
class GP_Glossary_Entry extends GP_Thing {

	var $table_basename = 'gp_glossary_entries';
	var $field_names = array( 'id', 'glossary_id', 'term', 'part_of_speech', 'comment', 'translation', 'date_modified', 'last_edited_by' );
	var $int_fields = array( 'id', 'glossary_id', 'last_edited_by' );
	var $non_updatable_attributes = array( 'id' );

	var $parts_of_speech = array();

	function __construct( $fields = array() ) {
		parent::__construct( $fields );
		$this->setup_pos();
	}

	function setup_pos(){
		if ( ! empty( $this->parts_of_speech ) ) {
			return;
		}

		$this->parts_of_speech = array(
			'noun'         => _x( 'noun', 'part-of-speech', 'glotpress' ),
			'verb'         => _x( 'verb','part-of-speech', 'glotpress' ),
			'adjective'    => _x( 'adjective', 'part-of-speech', 'glotpress' ),
			'adverb'       => _x( 'adverb', 'part-of-speech', 'glotpress' ),
			'interjection' => _x( 'interjection', 'part-of-speech', 'glotpress' ),
			'conjunction'  => _x( 'conjunction', 'part-of-speech', 'glotpress' ),
			'preposition'  => _x( 'preposition', 'part-of-speech', 'glotpress' ),
			'pronoun'      => _x( 'pronoun', 'part-of-speech', 'glotpress' ),
			'expression'   => _x( 'expression', 'part-of-speech', 'glotpress' )
		);
	}

	function restrict_fields( $glossary_entry ) {
		$glossary_entry->term_should_not_be( 'empty' );
		$glossary_entry->part_of_speech_should_not_be( 'empty' );
		$glossary_entry->glossary_id_should_be( 'positive_int' );
		$glossary_entry->last_edited_by_should_be( 'positive_int' );
	}

	function by_glossary_id( $glossary_id ) {
		return $this->many( "SELECT * FROM $this->table WHERE glossary_id= %d ORDER by term ASC", $glossary_id );
	}

	function last_modified( $glossary ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT date_modified FROM {$this->table} WHERE glossary_id = %d ORDER BY date_modified DESC LIMIT 1", $glossary->id, 'current' ) );
	}
}

GP::$glossary_entry = new GP_Glossary_Entry();