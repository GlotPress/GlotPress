<?php
/**
 * Template for errors.
 */

/** @var string $title */
/** @var string $message */

/* Translators: %s: title */
gp_title( esc_html( sprintf( __( '%s &lt; GlotPress', 'glotpress' ), $title ) ) );
gp_tmpl_header();
?>
	<div class="error-template">
		<h2><?php echo esc_html( $title ); ?></h2>
		<?php echo esc_html( $message ); ?>
	</div>
<?php
gp_tmpl_footer();
