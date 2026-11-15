<?php
/**
 * Template for the action buttons displayed below the editor
 *
 * @package    GlotPress
 * @subpackage Templates
 */

?>
<div class="actions">
	<div class="button-group">
		<?php if ( $can_edit ) : ?>
			<?php $add_translation_nonce = wp_create_nonce( 'add-translation_' . $translation->original_id ); ?>
			<button class="button is-primary ok" data-nonce="<?php echo esc_attr( $add_translation_nonce ); ?>">
				<?php _e( 'Save', 'glotpress' ); ?>
			</button>
			<?php if ( $can_approve_translation ) : ?>
				<button class="button ok" data-status="waiting" data-nonce="<?php echo esc_attr( $add_translation_nonce ); ?>">
					<?php _e( 'Save as waiting', 'glotpress' ); ?>
				</button>
			<?php endif; ?>
		<?php endif; ?>
		<button type="button" href="#" class="button is-link close"><?php _e( 'Cancel', 'glotpress' ); ?></button>
	</div>
</div>
