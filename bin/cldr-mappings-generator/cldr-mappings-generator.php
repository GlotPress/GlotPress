<?php
require __DIR__ . '/../../../../../wp-includes/pomo/translations.php';
require $locales_file = __DIR__ . '/../../locales/locales.php';

$pm = new CLDR_Plural_Mapper;
$pm->generate_mapping();
$pm->update_locales( $locales_file );

class CLDR_Plural_Mapper {
	private $cldr_plurals, $mapping = array(), $clashes = array(), $unknown = array();

	public function __construct() {
		// Update this file from https://raw.githubusercontent.com/unicode-cldr/cldr-core/master/supplemental/plurals.json
		$cldr_plurals = json_decode( file_get_contents( __DIR__ . '/plurals.json' ) );
		$this->cldr_plurals = $cldr_plurals->supplemental->{'plurals-type-cardinal'};
	}

	private function cldr_name_for_number( $locale, $number ) {
		if ( isset( $this->unknown[ $locale->slug ] ) ) {
			return false;
		}

		if ( ! isset( $locale->_cldr_name_for_number ) ) {
			$plurals = false;

			foreach ( array( 'lang_code_iso_639_1', 'lang_code_iso_639_2', 'lang_code_iso_639_3' ) as $slug ) {
				if ( isset( $this->cldr_plurals->{$locale->$slug} ) ) {
					$plurals = $this->cldr_plurals->{$locale->$slug};
					break;
				}
			}

			if ( ! $plurals ) {
				$this->unknown[ $locale->slug ] = $locale;
				return false;
			}

			// This converts the CLDR plurals function into PHP.
			// See http://unicode.org/reports/tr35/tr35-numbers.html#Operands

			$func_body = '$n = ltrim( $number, "-" );' . PHP_EOL;
			$func_body .= '$i = floor( $n );' . PHP_EOL;
			$func_body .= '$ni = strlen( $i );' . PHP_EOL;
			$func_body .= '$v = max( 0, strlen( $number ) - $ni - 1 );' . PHP_EOL;
			$func_body .= '$w = max( 0, strlen( rtrim( $number, "0" ) ) - $ni - 1 );' . PHP_EOL;
			$func_body .= '$f = intval( substr( $number, $ni + 1, $v ) );' . PHP_EOL;
			$func_body .= '$t = intval( substr( $number, $ni + 1, $w ) );' . PHP_EOL;

			foreach ( (array) $plurals as $type => $expression ) {
				$type = substr( $type, 17 );
				$expression = substr( $expression, 0, strpos( $expression, '@' ) );
				$expression = preg_replace( '#\b[nivwft]\b#', '$\0', $expression );
				$expression = preg_replace( '#\s=\s#', '==', $expression );
				$expression = preg_split( '#(and|or)#', $expression, 0, PREG_SPLIT_DELIM_CAPTURE );
				foreach ( $expression as $k => $exp ) {
					// Convert something like x = 2..4 to $x == 2 or x == 3 or x == 4
					if ( preg_match( '#^(.*?)(\d+)\.\.(\d+)#', $exp, $m ) ) {
						$new_expression = array();
						array_shift( $m );
						$body = array_shift( $m );

						for ( $i = $m[0]; $i <= $m[1]; $i++ ) {
							$new_expression[] = $body . $i;
						}
						$expression[$k] = '(' . implode( ' or ', $new_expression ) . ')';
					} elseif ( preg_match( '#^(.*?)(\d+)(?:,(\d+))#', $exp, $m ) ) {
					// Convert something like x = 2,4 to $x == 2 or x == 4
						array_shift( $m );
						$body = array_shift( $m );
						$new_expression = array();
						foreach ( $m as $i ) {
							$new_expression[] = $body . $i;
						}
						$expression[$k] = '(' . implode( ' or ', $new_expression ) . ')';
					}
				}
				$expression = trim( implode( $expression ) );
				if ( empty( $expression ) ) {
					$expression = 'true';
				}
				$func_body .= 'if (' . $expression . ') return "' . $type . '";' . PHP_EOL;
			}
			$func_body .= 'return "unknown";';
			$locale->_cldr_name_for_number = create_function( '$number', $func_body );
		}

		$f = $locale->_cldr_name_for_number;

		return $f( $number );
	}

	private function cldr_name_for_index( $locale, $index ) {
		$results = array();
		foreach ( $locale->numbers_for_index( $index, 10 ) as $number ) {
			$n = $this->cldr_name_for_number( $locale, $number );
			if ( false === $n ) return false;
			if ( ! isset( $results[$n] ) ) $results[$n] = 0;
			$results[$n] += 1;
		}

		if ( ! empty( $results ) ) {
			arsort( $results );
			return key( $results );
		}

		return false;
	}

	private function output_locales( $locales ) {
		$locales = array_map( function( $locale ) {
			$locale = GP_Locales::by_slug( $locale );
			return $locale->english_name . ' `' . $locale->slug . '`';
		}, array_keys( $locales ) );
		return implode( ', ', $locales );
	}

	public function generate_mapping() {
		echo 'Generating plurals mapping...';

		foreach ( GP_Locales::locales() as $slug => $locale ) {
			$this->clashes[$slug] = array();
			$this->mapping[$slug] = array();
			for ( $i = 0; $i < $locale->nplurals; $i++ ) {
				$cldr = $this->cldr_name_for_index( $locale, $i );
				if ( false === $cldr ) break;

				$this->clashes[$slug][$i] = $cldr;
				$this->mapping[$slug][$cldr] = $i;
			}

			if ( empty( $this->mapping[$slug] ) ) {
				unset( $this->clashes[$slug], $this->mapping[$slug] );
				continue;
			} elseif ( count($this->clashes[$slug]) != count($this->mapping[$slug]) ) {
				unset( $this->mapping[$slug] );
			} else {
				unset( $this->clashes[$slug] );
			}
		}
		echo 'done', PHP_EOL, PHP_EOL;
	}

	public function update_locales( $locales_file ) {
		$locales = file_get_contents( $locales_file );
		// Remove previous mappings.
		$locales = preg_replace( '/\t\t\$[^-]+->cldr_plurals_mapping.*\n/', '', $locales );

		// And update the file with new mappings.
		foreach ( $this->mapping as $slug => $map ) {
			preg_match( '/\$([^-]+)->slug\s*=\s*\'' . $slug . '\'/', $locales, $m );
			$var = $m[1];
			$p = strrpos( $locales, '$' . $var . '->' );
			$p = strpos( $locales, "\n", $p ) + 1;
			$locales = substr( $locales, 0, $p ) . "\t\t\${$var}->cldr_plurals_mapping = array( '" . implode( "', '", array_keys( $map ) ) . "' );\n" . substr( $locales, $p );
		}
		file_put_contents( $locales_file, $locales );

		if ( ! empty( $this->unknown ) ) {
			echo count( $this->unknown ), ' locales could not be found in CLDR (', $this->output_locales( $this->unknown ), ').', PHP_EOL, PHP_EOL;
		}
		if ( ! empty( $this->clashes ) ) {
			echo count( $this->clashes ), ' locales had different plurals definitions in CLDR (', $this->output_locales( $this->clashes ), ').', PHP_EOL, PHP_EOL;
		}
		echo 'Updated locales.php with ', count( $this->mapping ), ' CLDR plural mappings.', PHP_EOL;
	}
}
