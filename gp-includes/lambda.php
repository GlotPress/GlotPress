<?php
/**
 * create_function wrappers
 */

/**
 * Creates a function, which returns $value
 * 
 * @param mixed $value
 */
function returner( $value ) {
	return create_function( '', 'return '.var_export( $value, true ).';');
}

/**
 * Creates a function, which prints $value
 * 
 * @param mixed $value
 */
function echoer( $value ) {
	return create_function( '', 'echo '.var_export( $value, true ).';');
}

/**
 * Creates a function, which accepts $args and returns the expression in $expression.
 * 
 * Items from the optional array $locals can be used as local variables in the function.
 * In case of collision a formal arguments and a key in $locals, the latter will be prefixed
 * with ext_
 * 
 * @param string $args String with the function arguments
 * @param string $expression String with an expression -- the result of the function
 * @param array $locals The items in this array will be extracted in the function as local variables.
 */
function lambda( $args, $expression, $locals = array() ) {
	$export_call = $locals? 'extract('.var_export( $locals, true ).', EXTR_PREFIX_SAME, "ext");' : '';
	return create_function( $args, $export_call.' return ('.rtrim( $expression, '; ' ).');' );
}
