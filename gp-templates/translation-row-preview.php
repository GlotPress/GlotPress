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
	'0' => array( '', 'transparent', 'white' ),
	'1' => array( '&uarr;', 'transparent', 'green' ),
);
?>

<tr class="preview <?php gp_translation_row_classes( $t ); ?>" id="preview-<?php echo esc_attr( $t->row_id ) ?>" row="<?php echo esc_attr( $t->row_id ); ?>">
	<?php if ( $can_approve_translation ) : ?>
		<th scope="row" class="checkbox"><input type="checkbox" name="selected-row[]" /></th>
	<?php elseif ( $can_approve ) : ?>
		<th scope="row"></th>
	<?php endif; ?>
	<td class="priority" title="<?php echo esc_attr( sprintf( __( 'Priority: %s', 'glotpress' ), gp_array_get( GP::$original->get_static( 'priorities' ), $t->priority ) ) ); ?>">
	<?php echo $priority_char[ $t->priority ][0] // WPCS: XSS OK. ?>
	</td>
	<td class="original">
	<?php echo esc_translation( prepare_original( ( $t->singular ) ) ); ?>
	<?php if ( $t->context ) : ?>
			<span class="context bubble" title="<?php printf( __( 'Context: %s', 'glotpress' ), esc_html( $t->context ) ); ?>"><?php echo esc_html( $t->context ); ?></span>
	<?php endif; ?>

	</td>
	<td class="translation foreign-text">
	<?php
	if ( $can_edit ) {
		$edit_text = __( 'Double-click to add', 'glotpress' );
	} elseif ( is_user_logged_in() ) {
		$edit_text = __( 'You are not allowed to add a translation.', 'glotpress' );
	} else {
		$edit_text = sprintf( __( 'You <a href="%s">have to log in</a> to add a translation.', 'glotpress' ), esc_url( wp_login_url( gp_url_current() ) ) );
	}

	$missing_text = "<span class='missing'>$edit_text</span>"; // WPCS: XSS OK.
	if ( ! count( array_filter( $t->translations, 'gp_is_not_null' ) ) ) :
		echo $missing_text;
		elseif ( ! $t->plural ) :
			echo esc_translation( $t->translations[0] );
		else : ?>
			<ul>
				<?php
				foreach ( $t->translations as $translation ) :
		?>
					<li><?php echo gp_is_empty_string( $translation ) ? $missing_text : esc_translation( $translation ); ?></li>
		<?php
				endforeach;
				?>
			</ul>
	<?php
		endif;
	?>
	</td>
	<td class="actions">
		<a href="#" row="<?php echo $t->row_id; ?>" class="action edit"><?php _e( 'Details', 'glotpress' ); ?></a>
	</td>
</tr>
