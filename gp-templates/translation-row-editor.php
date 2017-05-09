<?php
/**
 * Template for the editor part of a single translation row in a translation set display
 *
 * @package    GlotPress
 * @subpackage Templates
 */

?>
<tr class="editor <?php echo gp_translation_row_classes( $t ); ?>" id="editor-<?php echo esc_attr( $t->row_id ); ?>" row="<?php echo esc_attr( $t->row_id ); ?>">
	<td colspan="<?php echo $can_approve ? 5 : 4 ?>">
		<div class="strings">
	<?php
	$singular = isset( $t->singular_glossary_markup ) ? $t->singular_glossary_markup : esc_translation( $t->singular );
	$plural   = isset( $t->plural_glossary_markup ) ? $t->plural_glossary_markup : esc_translation( $t->plural );
	?>

	<?php if ( ! $t->plural ) : ?>
				<p class="original"><?php echo prepare_original( $singular ); ?></p>
				<?php textareas( $t, array( $can_edit, $can_approve_translation ) ); ?>
	<?php else : ?>
				<?php if ( absint( $locale->nplurals ) === 2 && 'n != 1' === $locale->plural_expression ) : ?>
					<p><?php printf( __( 'Singular: %s', 'glotpress' ), '<span class="original">' . esc_html( $singular ) . '</span>' ); ?></p>
		<?php textareas( $t, array( $can_edit, $can_approve ), 0 ); ?>
					<p class="clear">
		<?php printf( __( 'Plural: %s', 'glotpress' ), '<span class="original">' . esc_html( $plural ) . '</span>' ); ?>
					</p>
		<?php textareas( $t, array( $can_edit, $can_approve ), 1 ); ?>
				<?php else : ?>
					<!--
					TODO: labels for each plural textarea and a sample number
					-->
					<p><?php printf( __( 'Singular: %s', 'glotpress' ), '<span class="original">' . esc_html( $singular ) . '</span>' ); ?></p>
					<p class="clear">
		<?php printf( __( 'Plural: %s', 'glotpress' ), '<span class="original">' . esc_html( $plural ) . '</span>' ); ?>
					</p>
		<?php foreach ( range( 0, $locale->nplurals - 1 ) as $plural_index ) : ?>
		<?php if ( $locale->nplurals > 1 ) : ?>
							<p class="plural-numbers">
								<?php printf( __( 'This plural form is used for numbers like: %s', 'glotpress' ),
								'<span class="numbers">' . implode( ', ', $locale->numbers_for_index( $plural_index ) ) . '</span>' ); ?></p>
		<?php endif; ?>
		<?php textareas( $t, array( $can_edit, $can_approve ), $plural_index ); ?>
		<?php endforeach; ?>
				<?php endif; ?>
	<?php endif; ?>
		</div>
	<?php gp_tmpl_load( 'translation-row-editor-meta', get_defined_vars() ); ?>
		<div class="actions">
	<?php if ( $can_edit ) : ?>
				<button class="ok" data-nonce="<?php echo esc_attr( wp_create_nonce( 'add-translation_' . $t->original_id ) ); ?>">
		<?php echo $can_approve_translation ? __( 'Add translation &rarr;', 'glotpress' ) : __( 'Suggest new translation &rarr;', 'glotpress' ); ?>
				</button>
	<?php endif; ?>
	<?php _e( 'or', 'glotpress' ); ?> <a href="#" class="close"><?php _e( 'Cancel', 'glotpress' ); ?></a>
		</div>
	</td>
</tr>
