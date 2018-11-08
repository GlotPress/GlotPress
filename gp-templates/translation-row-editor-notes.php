<?php
/**
 * Template for the notes section of the editor row in a translation set display
 *
 * @package    GlotPress
 * @subpackage Templates
 */

$notes = GP::$notes->get_by_translation_id( $translation->id );
if ( $translation->translation_status ) {
	if ( count( $notes ) > 0 ) {
?>
<div class="notes">
	<h3><?php _e( 'Translation Notes', 'glotpress' ); ?></h3>
	<dl class="notes-list">
		<?php
		foreach ( $notes as $note ) {
			gp_tmpl_load( 'note', get_defined_vars() );
		}
		?>
	</dl>
	<dl>
	<?php
	}

	if ( GP::$permission->current_user_can(
		'approve',
		'translation',
		$translation->id,
		array(
			'translation' => $translation,
		)
	) || get_current_user_id() === $translation->user_id ) {
		echo '<dt><br>' . __( 'New Reviewer note:', 'glotpress' ) . '</dt><br>';
	?>
			<dt><?php _e( 'New note:', 'glotpress' ); ?></dt>
			<dt><textarea autocomplete="off" class="foreign-text" name="note[<?php echo esc_attr( $translation->row_id ); ?>]" id="note_<?php echo esc_attr( $translation->row_id ); ?>"></textarea></dt>
			<dt><button class="add-note" tabindex="-1" data-nonce="<?php echo esc_attr( wp_create_nonce( 'new-note-' . $translation->id ) ); ?>"><?php _e( 'Add Note', 'glotpress' ); ?></button></dt>
	<?php
	}
	?>
	</dl>
</div>
<?php
}
?>
