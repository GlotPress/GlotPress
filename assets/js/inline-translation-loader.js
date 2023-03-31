/* global gpInlineTranslation */

( function( $ ) {
	$( function() {
		if ( ! $( '#translator-launcher' ).length || typeof gpInlineTranslation !== 'object' ) {
			// gpInlineTranslation not available, maybe interface is in English
			return false;
		}

		function loadTranslator() {
			$( '#translator-launcher .text' ).addClass( 'enabled' ).removeClass( 'disabled' );
			if ( gpInlineTranslation.load() !== false ) {
				// was loaded successfully
				autoloadTranslator( true );
			}
		}

		function unloadTranslator() {
			$( '#translator-launcher .text' ).removeClass( 'enabled' ).addClass( 'disabled' );
			gpInlineTranslation.unload();
			autoloadTranslator( false );
		}

		$( document.body ).on( 'click', '#translator-launcher', function() {
			if ( $( '#translator-launcher .text' ).hasClass( 'disabled' ) ) {
				loadTranslator();
			} else {
				unloadTranslator();
			}
			return false;
		} );

		// only show the button when the translator has been loaded
		runWhenTranslatorIsLoaded( function() {
			$( '#translator-launcher' ).show();
			if ( shouldAutoloadTranslator() ) {
				loadTranslator();
			}
		} );

		// because of the nature of wp_enqueue_script and the fact that we can only insert the gpInlineTranslation at the bottom of the page, we have to wait until the object exists
		function runWhenTranslatorIsLoaded( callback ) {
			if ( 'undefined' === typeof window.gpInlineTranslation ) {
				window.setTimeout( function() {
					runWhenTranslatorIsLoaded( callback );
				}, 100 );
				return;
			}
			callback();
		}

		function autoloadTranslator( enable ) {
			if ( enable ) {
				document.cookie = 'autoinlinetranslation=1;path=/';
			} else {
				document.cookie = 'autoinlinetranslation=;expires=Sat,%201%20Jan%202000%2000:00:00%20GMT;path=/';
			}
		}

		function shouldAutoloadTranslator() {
			// also enable if the gp_enable_inline_translation field from the HTTP POST is set
			if ( $( 'input[name="gp_enable_inline_translation"]' ).prop( 'checked' ) ) {
				return true;
			}

			return !! document.cookie.match( /autoinlinetranslation=1/ );
		}
	} );
}( jQuery ) );

