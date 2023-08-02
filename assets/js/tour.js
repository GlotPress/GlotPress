jQuery( document ).ready(
	function() {
		jQuery( document ).on( 'click', '.close-tour', function() {
			jQuery( this ).closest( '.webui-popover' ).hide();
			return false;
		} );
		jQuery( document ).on( 'click', '.next-tour-item', function() {
			jQuery( this ).closest( '.webui-popover' ).hide();
			jQuery( '.pulse-wrapper .tour-' + jQuery( this ).data( 'tourname' ) + ':visible' ).first().click();
			return false;
		} );
		jQuery( document ).on( 'click', '.reveal-next-tour-item', function() {
			jQuery( this ).closest( '.webui-popover' ).hide();
			jQuery( jQuery( this ).data( 'reveal' ) ).first().click();
			return false;
		} );
		jQuery( document ).on( 'click', '.dismiss-tour', function() {
			jQuery( this ).closest( '.webui-popover' ).hide();
			return false;
		} );
		jQuery( document ).on( 'click', '.pulse', function() {
			const wrapper = jQuery( this ).closest( '.pulse-wrapper' );
			const tourName = wrapper.data( 'tourname' );
			const nextItem = 1 + wrapper.data( 'tourindex' );
			const tourEndsHere = typeof window.tour[tourName][nextItem] === 'undefined';

			let popover_content = wrapper.data( 'popover-content' );
			if ( tourEndsHere ) {
				popover_content += '<br/><br/><a href="" class="close-tour">Close</a>'
			} else if ( typeof window.tour[tourName][nextItem] !== 'undefined' &&  typeof window.tour[tourName][nextItem].reveal === 'undefined') {
				popover_content += '<br/><br/><a href="" class="next-tour-item" data-tourname="' + tourName + '">Next</a>'
			} else if ( typeof window.tour[tourName][nextItem].reveal !== 'undefined' ) {
				popover_content += '<br/><br/><a href="" class="reveal-next-tour-item" data-reveal="' + window.tour[tourName][nextItem].reveal + '">Reveal Next Step</a>'
			}
			if ( ! tourEndsHere ) {
				popover_content += '<br/><br/><small><a href="" class="dismiss-tour">Dismiss this tour';
			}
			WebuiPopovers.show( this, { title: window.tour[tourName][0].title, content: popover_content, width: 300, dismissible: true } );
			jQuery( '.tour-' + tourName ).remove();

			jQuery( '.tour-' + tourName ).remove();
			if ( tourEndsHere ) {
				return;
			}
			const item = window.tour[tourName][nextItem];
			addPulse( jQuery(item.selector), item.html, tourName, nextItem );

		} );
		function addPulse( field, html, tourName, index ) {
			var div = jQuery( '<div class="pulse-wrapper">' );
			div.data( 'tourname', tourName ).data( 'tourindex', index ).data( 'popover-content', html );
			field.wrap( div );
			var pulse = jQuery( '<div class="pulse tour-' + tourName + '">' );
			field.parent().append( pulse );
		}
		window.tour = gp_tour;
		window.loadTour = function(){
			for ( const n in window.tour ) {
				const color1 = window.tour[n][0].color + '00';
				const color2 = window.tour[n][0].color + 'a0';
				var sheet = document.styleSheets[0];
				sheet.insertRule(`@keyframes animation-${n} {
					0% {
					  box-shadow: 0 0 0 0 ${color2};
					}
					70% {
					  box-shadow: 0 0 0 10px ${color1};
					}
					100% {
					  box-shadow: 0 0 0 0 ${color1};
					}`, sheet.cssRules.length);
				
				sheet.insertRule(`.tour-${n} {
					box-shadow: 0 0 0 ${color2};
					background: ${color1};
					-webkit-animation: animation-${n} 2s infinite;
					animation: animation-${n} 2s infinite;
					}`, sheet.cssRules.length);

				addPulse( jQuery( window.tour[n][1].selector ), window.tour[n][1].html, n, 1 );
			

				
			}
		}
		window.loadTour();
		// addPulse( jQuery( '.source-string .glossary-word:first' ), 'Please follow this translation recommendation', function() {
		// 	addPulse( jQuery( '.translation-actions__save' ), 'Don\'t forget to submit your translation', function() {
		// 	} );
		// } );
		// addPulse( jQuery( '.revealing.filter' ), 'Click here to reveal the search field', function() {
		// 	addPulse( jQuery( '.filters-expanded input.is-primary' ), 'Click here to search', function() {
		// 		addPulse( jQuery( '.glossary-word' ), 'Please follow this translation recommendation' );
		// 	} );
		// } );
	}
);