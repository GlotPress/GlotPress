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
		<?php echo $priority_char[ $translation->priority ][0]; // WPCS: XSS OK. ?>
	</td>
	<td class="original">
		<?php echo prepare_original( $translation_singular ); // WPCS: XSS OK. ?>
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
			$edit_text = sprintf( __( 'You <a href="%s">have to log in</a> to add a translation.', 'glotpress' ), esc_url( wp_login_url( gp_url_current() ) ) ); // WPCS: XSS OK.
		}

		$missing_text = "<span class='missing'>$edit_text</span>"; // WPCS: XSS OK.
		if ( ! count( array_filter( $translation->translations, 'gp_is_not_null' ) ) ) :
			echo $missing_text; // WPCS: XSS OK.
		elseif ( ! $translation->plural ) :
			echo esc_translation( $translation->translations[0] ); // WPCS: XSS OK.
		else :
		?>
			<ul>
				<?php foreach ( $translation->translations as $translation ) : ?>
					<li>
					<?php echo gp_is_empty_string( $translation ) ? $missing_text : esc_translation( $translation ); // WPCS: XSS OK. ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</td>
	<td class="actions">
		<a href="#" row="<?php echo $translation->row_id; // WPCS: XSS OK. ?>" class="action edit"><?php _e( 'Details', 'glotpress' ); ?></a>
	</td>
</tr>
