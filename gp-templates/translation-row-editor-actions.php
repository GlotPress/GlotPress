<?php
/**
 * Template for the action buttons displayed below the editor
 *
 * @package    GlotPress
 * @subpackage Templates
 */

?>
<div class="actions">
	<?php if ( $can_edit ) : ?>
		<button class="ok" data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-translation_' . $translation->original_id ) ); ?>">
			<?php echo $can_approve_translation ? __( 'Add translation &rarr;', 'glotpress' ) : __( 'Suggest new translation &rarr;', 'glotpress' ); ?>
		</button>
	<?php endif; ?>
	<?php _e( 'or', 'glotpress' ); ?> <a href="#" class="close"><?php _e( 'Cancel', 'glotpress' ); ?></a>
</div>
