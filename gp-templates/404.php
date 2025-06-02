<?php
gp_title( __( 'Not Found &lt; GlotPress', 'glotpress' ) );
gp_tmpl_header();
?>
<div class="error-template">
	<h2><?php esc_html_e( 'Not Found', 'glotpress' ); ?></h2>
	<?php esc_html_e( 'The requested URL was not found on this server.', 'glotpress' ); ?>
</div>
<?php
gp_tmpl_footer();
