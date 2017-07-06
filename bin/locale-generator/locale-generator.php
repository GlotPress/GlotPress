<?php

include( '../locales/locales/php' );

include( '../../CLDR to gettext Plural Rules/src/FormulaConverter.php' );

$cldr_data = json_decode( file_get_contents( '../../CLDR to gettext Plural Rules/src/cldr-data/supplimental/plurals.json' ) );
