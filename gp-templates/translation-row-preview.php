<?php
/**
 * Template for the preview part of a single translation row in a translation set display
 *
 * @package    GlotPress
 * @subpackage Templates
 */

$priority_char = array(
	'-2' => array( '&times;', 'transparent', '#ccc' ),
	'-1' => array( '&darr;', 'transparent', 'blue' ),
	'0'  => array( '', 'transparent', 'white' ),
	'1'  => array( '&uarr;', 'transparent', 'green' ),
);

?>

<tr class="preview <?php gp_translation_row_classes( $translation ); ?>" id="preview-<?php echo esc_attr( $translation->row_id ); ?>" row="<?php echo esc_attr( $translation->row_id ); ?>">
	<?php if ( $can_approve_translation ) : ?>
		<th scope="row" class="checkbox"><input type="checkbox" name="selected-row[]"/></th>
	<?php elseif ( $can_approve ) : ?>
		<th scope="row"></th>
	<?php endif; ?>
	<?php /* translators: %s: Priority of original */ ?>
	<td class="priority" title="<?php echo esc_attr( sprintf( __( 'Priority: %s', 'glotpress' ), gp_array_get( GP::$original->get_static( 'priorities' ), $translation->priority ) ) ); ?>">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $priority_char[ $translation->priority ][0];
		?>
	</td>
	<td class="original">
		<?php
		if ( ! $translation->plural ) {
			?>
			<span class="original-text"><?php echo prepare_original( $translation_singular ); ?></span>
			<?php
		} else {
			$translation_plural = isset( $translation->plural_glossary_markup ) ? $translation->plural_glossary_markup : prepare_original( esc_translation( $translation->plural ) );
			?>
			<ul>
				<li><small><?php esc_html_e( 'Singular:', 'glotpress' ); ?></small><br><span class="original-text"><?php echo prepare_original( $translation_singular ); ?></span></li>
				<li><small><?php esc_html_e( 'Plural:', 'glotpress' ); ?></small><br><span class="original-text"><?php echo prepare_original( $translation_plural ); ?></span></li>
			</ul>
			<?php
		}
		?>
		<?php if ( $translation->context ) : ?>
			<?php /* translators: %s: Context of original */ ?>
			<span class="context bubble" title="<?php echo esc_attr( sprintf( __( 'Context: %s', 'glotpress' ), $translation->context ) ); ?>"><?php echo esc_html( $translation->context ); ?></span>
		<?php endif; ?>
	</td>
	<td class="translation foreign-text">
		<?php
		if ( $can_edit ) {
			$edit_text = __( 'Double-click to add', 'glotpress' );
		} elseif ( is_user_logged_in() ) {
			$edit_text = __( 'You are not allowed to add a translation.', 'glotpress' );
		} else {
			/* translators: %s: url */
			$edit_text = sprintf( __( 'You <a href="%s">have to log in</a> to add a translation.', 'glotpress' ), esc_url( wp_login_url( gp_url_current() ) ) );
		}

		$missing_text = "<span class='missing'>$edit_text</span>";
		if ( ! count( array_filter( $translation->translations, 'gp_is_not_null' ) ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $missing_text;
		} elseif ( ! $translation->plural || 1 === $locale->nplurals ) {
			echo '<span class="translation-text">' . prepare_original( esc_translation( $translation->translations[0] ) ) . '</span>';
		} elseif ( $translation->plural && 2 === $locale->nplurals && 'n != 1' === $locale->plural_expression ) {
			?>
			<ul>
				<li>
					<small><?php esc_html_e( 'Singular:', 'glotpress' ); ?></small><br>
					<?php
					if ( ! isset( $translation->translations[0] ) || gp_is_empty_string( $translation->translations[0] ) ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $missing_text;
					} else {
						echo '<span class="translation-text">' . prepare_original( esc_translation( $translation->translations[0] ) ) . '</span>';
					}
					?>
				</li>
				<li>
					<small><?php esc_html_e( 'Plural:', 'glotpress' ); ?></small><br>
					<?php
					if ( ! isset( $translation->translations[1] ) || gp_is_empty_string( $translation->translations[1] ) ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $missing_text;
					} else {
						echo '<span class="translation-text">' . prepare_original( esc_translation( $translation->translations[1] ) ) . '</span>';
					}
					?>
				</li>
			</ul>
			<?php
		} else {
			?>
			<ul>
				<?php
				foreach ( range( 0, $locale->nplurals - 1 ) as $plural_index ) {
					$plural_string = implode( ', ', $locale->numbers_for_index( $plural_index ) );
					?>
					<li>
						<small>
							<?php
							printf(
								/* translators: %s: Plural form. */
								esc_html__( '%s:', 'glotpress' ),
								esc_html( $plural_string )
							);
							?>
						</small><br>
						<?php
						if ( ! isset( $translation->translations[ $plural_index ] ) || gp_is_empty_string( $translation->translations[ $plural_index ] ) ) {
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $missing_text;
						} else {
							echo '<span class="translation-text">' . prepare_original( esc_translation( $translation->translations[ $plural_index ] ) ) . '</span>';
						}
						?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>
	</td>
	<td class="actions">
		<a href="#" class="action edit"><?php _e( 'Details', 'glotpress' ); ?></a>
	</td>
</tr>
