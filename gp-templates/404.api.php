<?php
/**
 * API: 404
 *
 * @package GlotPress
 * @subpackage Templates
 */

echo wp_json_encode(
	array(
		'success' => false,
		'error'   => __( 'Not Found', 'glotpress' ),
	)
);
