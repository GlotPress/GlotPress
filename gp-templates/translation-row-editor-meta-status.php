<?php
/**
 * Template for the meta section of the editor row in a translation set display
 *
 * @package    GlotPress
 * @subpackage Templates
 */

?>
<dl>
	<dt><?php _e( 'Status:', 'glotpress' ); ?></dt>
	<dd>
		<?php echo display_status( $translation->translation_status ); ?>
		<?php if ( $translation->translation_status ) : ?>
			<?php if ( $can_approve_translation ) : ?>
				<?php if ( 'current' !== $translation->translation_status ) : ?>
					<button class="button is-small approve" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-current_' . $translation->id ) ); ?>" title="<?php esc_attr_e( 'Approve this translation. Any existing translation will be kept as part of the translation history.', 'glotpress' ); ?>"><strong>+</strong> <span><?php _ex( 'Approve', 'Action', 'glotpress' ); ?></span></button>
				<?php endif; ?>
				<?php if ( 'rejected' !== $translation->translation_status ) : ?>
					<button class="button is-small reject" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $translation->id ) ); ?>" title="<?php esc_attr_e( 'Reject this translation. The existing translation will be kept as part of the translation history.', 'glotpress' ); ?>"><strong>&minus;</strong> <?php _ex( 'Reject', 'Action', 'glotpress' ); ?></button>
				<?php endif; ?>
				<?php if ( 'fuzzy' !== $translation->translation_status ) : ?>
					<button class="button is-small fuzzy" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $translation->id ) ); ?>" title="<?php esc_attr_e( 'Mark this translation as fuzzy for further review.', 'glotpress' ); ?>"><strong>~</strong> <span><?php _ex( 'Fuzzy', 'Action', 'glotpress' ); ?></span></button>
				<?php endif; ?>
			<?php elseif ( $can_reject_self ) : ?>
				<button class="button is-small reject" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-rejected_' . $translation->id ) ); ?>" title="<?php esc_attr_e( 'Reject this translation. The existing translation will be kept as part of the translation history.', 'glotpress' ); ?>"><strong>&minus;</strong> <span><?php _ex( 'Reject', 'Action', 'glotpress' ); ?></span></button>
				<button class="button is-small fuzzy" data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-translation-status-fuzzy_' . $translation->id ) ); ?>" title="<?php esc_attr_e( 'Mark this translation as fuzzy for further review.', 'glotpress' ); ?>"><strong>~</strong> <span><?php _ex( 'Fuzzy', 'Action', 'glotpress' ); ?></span></button>
			<?php endif; ?>
		<?php endif; ?>
	</dd>
</dl>
