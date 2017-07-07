<?php
/**
 * Template for the editor part of a single translation row in a translation set display
 *
 * @package    GlotPress
 * @subpackage Templates
 */

/**
 * Filter to update colspan of editor. Decrease to add an extra column
 * with action 'gp_translation_row_editor_columns'.
 *
 * @since 3.0.0
 *
 * @param int $colspan The colspan of editor column.
 */
$colspan = apply_filters( 'gp_translation_row_editor_colspan', $can_approve ? 5 : 4 );

$singular = sprintf(
	/* translators: %s: Original singular form of the text */
	__( 'Singular: %s', 'glotpress' ),
	'<span class="original">' . $translation_singular . '</span>'
);
$plural = sprintf(
	/* translators: %s: Original plural form of the text */
	__( 'Plural: %s', 'glotpress' ),
	'<span class="original">' . ( isset( $translation->plural_glossary_markup ) ? $translation->plural_glossary_markup : esc_translation( $translation->plural ) ) . '</span>'
);

?>
<tr class="editor <?php gp_translation_row_classes( $translation ); ?>" id="editor-<?php echo esc_attr( $translation->row_id ); ?>" row="<?php echo esc_attr( $translation->row_id ); ?>">
	<td colspan="<?php echo esc_attr( $colspan ); ?>">
		<div class="strings">
		<?php
			$singular = isset( $translation->singular_glossary_markup ) ? $translation->singular_glossary_markup : esc_translation( $translation->singular );
			$plural   = isset( $translation->plural_glossary_markup ) ? $translation->plural_glossary_markup : esc_translation( $translation->plural );
			$nplurals = $locale->get_nplurals( $project->plurals_type );
		?>

			<?php if ( ! $translation->plural ) : ?>
				<p class="original"><?php echo prepare_original( $translation_singular );  // WPCS: XSS OK. ?></p>
				<p class="original_raw"><?php echo esc_translation( $translation->singular ); // WPCS: XSS ok. ?></p>
				<?php textareas( $translation, array( $can_edit, $can_approve_translation ) ); ?>
			<?php else : ?>
				<?php if ( 'gettext' === $project->plurals_type && 2 === $nplurals && 'n != 1' === $locale->plural_expression ) : ?>
					<p>
					<?php
						// Translators: %s is the original (singlular) string.
						printf( __( 'Singular: %s', 'glotpress' ), // WPCS: XSS ok.
							'<span class="original">' . $singular . '</span>'
						);
					?>
					</p>
					<?php
						textareas( $translation, array( $can_edit, $can_approve ), 0 );
					?>
					<p class="clear">
						<?php echo $plural;  // WPCS: XSS OK. ?>
						// Translators: %s is the original plural string.
						printf( __( 'Plural: %s', 'glotpress' ), // WPCS: XSS ok.
							'<span class="original">' . $plural . '</span>'
						);
					?>
					</p>
					<?php textareas( $translation, array( $can_edit, $can_approve ), 1 ); ?>
				<?php else : ?>
					<!--
					TODO: labels for each plural textarea and a sample number
					-->
					<p>
					<?php
						// Translators: %s is the original (singlular) string.
						printf( __( 'Singular: %s', 'glotpress' ), // WPCS: XSS ok.
							'<span class="original">' . $singular . '</span>'
						);
					?>
					</p>
					<p class="clear">
					<?php
						// Translators: %s is the original plural string.
						printf( __( 'Plural: %s', 'glotpress' ),  // WPCS: XSS ok.
							'<span class="original">' . $plural . '</span>'
						);
					?>
					</p>
					<?php foreach ( range( 0, $nplurals - 1 ) as $plural_index ) : ?>
						<?php if ( $nplurals > 1 ) : ?>
							<p class="plural-numbers">
							<?php
								// Translators: %s is the list of examples.
								printf( __( 'This plural form is used for numbers like: %s', 'glotpress' ), // WPCS: XSS ok.
									'<span class="numbers">' . $locale->get_plural_example( $project->plurals_type, $plural_index ) . '</span>'
								);
							?>
							</p>
						<?php endif; ?>
						<?php textareas( $translation, array( $can_edit, $can_approve ), $plural_index ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			<?php endif; ?>
			<?php gp_tmpl_load( 'translation-row-editor-actions', get_defined_vars() ); ?>
		</div>
		<?php gp_tmpl_load( 'translation-row-editor-meta', get_defined_vars() ); ?>
		<?php gp_tmpl_load( 'translation-row-editor-notes', get_defined_vars() ); ?>
	</td>
	<?php
	/**
	 * Fires after editor column.
	 *
	 * @since 3.0.0
	 *
	 * @param GP_Translation     $translation The current translation.
	 * @param GP_Translation_Set $translation_set The current translation set.
	 */
	do_action( 'gp_translation_row_editor_columns', $translation, $translation_set );
	?>
</tr>
