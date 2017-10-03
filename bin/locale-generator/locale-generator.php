<?php
/**
 * Geneate the locale object infomration in PHP format.
 *
 * @package GlotPress
 */

// Include the current locales.
include( '../../locales/locales.php' );

/*
 * Get the CLDR Data.
 *
 * By default it is read from the plurals.json file in the current directory, you can download
 * the file from the CLDR GitHub project:
 *
 *     https://raw.githubusercontent.com/unicode-cldr/cldr-core/master/supplemental/plurals.json
 *
 * or the script will do it automatically if the local file does not exist.
 *
 */
if ( ! file_exists( 'plurals.json' ) ) {
	file_put_contents( 'plurals.json', file_get_contents( 'https://raw.githubusercontent.com/unicode-cldr/cldr-core/master/supplemental/plurals.json' ) );
}

$cldr_data = json_decode( file_get_contents( 'plurals.json' ), true );

$cldr_locales = $cldr_data['supplemental']['plurals-type-cardinal'];

// Create a working locales object.
$locales = new GP_Locales();

// Run through the locales and see if we can find a matching CLDR locale.
foreach ( $locales->locales as $key => $value ) {
	// Flush the old CLDR data.
	unset( $locales->locales[ $key ]->cldr_code );
	unset( $locales->locales[ $key ]->cldr_nplurals );
	$locales->locales[ $key ]->cldr_plural_expressions = array(
		'zero'  => '',
		'one'   => '',
		'two'   => '',
		'few'   => '',
		'many'  => '',
		'other' => '',
	);

	// Check the iso codes against the CLDR data in accending order.
	if ( array_key_exists( $locales->locales[ $key ]->lang_code_iso_639_1, $cldr_locales ) ) {
		$locales->locales[ $key ]->cldr_code = $locales->locales[ $key ]->lang_code_iso_639_1;
	} elseif ( array_key_exists( $locales->locales[ $key ]->lang_code_iso_639_2, $cldr_locales ) ) {
		$locales->locales[ $key ]->cldr_code = $locales->locales[ $key ]->lang_code_iso_639_2;
	} elseif ( array_key_exists( $locales->locales[ $key ]->lang_code_iso_639_3, $cldr_locales ) ) {
		$locales->locales[ $key ]->cldr_code = $locales->locales[ $key ]->lang_code_iso_639_3;
	}

	// If we found a matching CLDR code, add the plurals data to the GP locales.
	if ( $locales->locales[ $key ]->cldr_code ) {
		// Set the number of CLDR plurals.
		$locales->locales[ $key ]->cldr_nplurals = count( $cldr_locales[ $locales->locales[ $key ]->cldr_code ] );

		// Loop through the CLDR plural rules and set them according to their type.
		foreach ( $cldr_locales[ $locales->locales[ $key ]->cldr_code ] as $type => $rule ) {
			switch ( $type ) {
				case 'pluralRule-count-zero':
					$locales->locales[ $key ]->cldr_plural_expressions['zero'] = $rule;

					break;
				case 'pluralRule-count-one':
					$locales->locales[ $key ]->cldr_plural_expressions['one'] = $rule;

					break;
				case 'pluralRule-count-two':
					$locales->locales[ $key ]->cldr_plural_expressions['two'] = $rule;

					break;
				case 'pluralRule-count-few':
					$locales->locales[ $key ]->cldr_plural_expressions['few'] = $rule;

					break;
				case 'pluralRule-count-many':
					$locales->locales[ $key ]->cldr_plural_expressions['many'] = $rule;

					break;
				case 'pluralRule-count-other':
					$locales->locales[ $key ]->cldr_plural_expressions['other'] = $rule;

					break;
			}
		}
	}
}

// Use the new GP Locales object to create output that can be added in to the locales.php file.
generate_output( $locales );
/**
 * Function to output the locales data in PHP source format.
 *
 * @param GP_Locales $locales The GlotPress Locales object.
 */
function generate_output( $locales ) {
	// Get all the GP_Locale variables we're going to output.
	$vars = get_object_vars( $locales->locales['en'] );

	// Loop through all of the locales.
	foreach ( $locales->locales as $locale ) {
		// Create the variable name we'll use to define the locale based on the slug with any dashes replaced with underscores.
		$var_name = str_replace( '-', '_', $locale->slug );
		$root_var_name = str_replace( '-', '_', $locale->variant_root );

		// Output the first line to 'create' the GP_Locale object for this locale.
		echo "\t\t\${$var_name} = new GP_Locale();\n";

		// Now loop through all the variables that may be set for this locale.
		foreach ( $vars as $var => $value ) {
			// Handle variables that are arrays for output.
			if ( is_array( $locale->$var ) ) {
				// Loop through all of the array keys to output.
				foreach ( $locale->$var as $key => $value ) {
					// Don't output empty variables or the speical case of 'variants' (they will be outputed with the variant locale instead).
					if ( ! empty( $locale->$var[ $key ] ) && 'variants' !== $var ) {
						// Create some space to make the output pretty and they output the line.
						$padding = str_repeat( ' ', 36 - strlen( $var ) - ( strlen( $var_name ) - 2 ) - strlen( $key ) - 4 );
						echo "\t\t\${$var_name}->{$var}['{$key}']{$padding} = '" . str_replace( "'", "\'", $locale->$var[ $key ] ) . "';\n";
					}
				}
			} else {
				// Don't output empty variables.
				if ( ! empty( $locale->$var ) ) {
					// Create some space to make the output pretty and they output the line.
					$padding = str_repeat( ' ', 36 - strlen( $var ) - ( strlen( $var_name ) - 2 ) );
					echo "\t\t\${$var_name}->{$var}{$padding} = '" . str_replace( "'", "\'", $locale->$var ) . "';\n";

					// Handle the special case of locales with variants, output the line to create the root's variant list here.
					if ( 'variant_root' === $var ) {
						// Create some space to make the output pretty and they output the line.
						$padding = str_repeat( ' ', 36 - strlen( $root_var_name ) - ( strlen( $root_var_name ) - 2 ) - 15 );
						echo "\t\t\${$root_var_name}->variants['{$var_name}']{$padding} = \${$root_var_name}->english_name;\n";
					}
				}
			}
		}

		// Add some space between locales for easier reading.
		echo "\n";
	}
}
