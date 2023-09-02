/* global wp, document, window, WebuiPopovers, gp_tour */
/* eslint camelcase: "off" */

jQuery( document ).ready(
	function() {
		jQuery( document ).on( 'click', '.close-tour, .dismiss-tour', function() {
			jQuery( this ).closest( '.webui-popover' ).hide();
			jQuery( '.pulse-wrapper' ).removeClass( 'pulse-border' );
			return false;
		} );
		jQuery( document ).on( 'click', '.next-tour-item', function() {
			jQuery( this ).closest( '.webui-popover' ).hide();
			jQuery( '.pulse-wrapper .tour-' + jQuery( this ).data( 'tourname' ) + ':visible' ).first().click();
			return false;
		} );
		jQuery( document ).on( 'click', '.previous-tour-item', function() {
			var currentPopover = jQuery( this ).closest( '.webui-popover' );
			if ( currentPopover.prev().hasClass( 'webui-popover' ) ) {
				currentPopover.hide();
				currentPopover.prev().show();
			}
			return false;
		} );
		jQuery( document ).on( 'click', '.reveal-next-tour-item', function() {
			jQuery( this ).closest( '.webui-popover' ).hide();
			jQuery( jQuery( this ).data( 'reveal' ) ).first().click();
			return false;
		} );

		jQuery( document ).on( 'click', '.pulse', function() {
			var wrapper = jQuery( this ).closest( '.pulse-wrapper' );
			var tourName = wrapper.data( 'tourname' );
			var nextItem = 1 + wrapper.data( 'tourindex' );
			var tourEndsHere = typeof window.tour[ tourName ][ nextItem ] === 'undefined';
			var showPreviousBtn = wrapper.data( 'tourindex' ) > 1;
			var popoverContent = wrapper.data( 'popover-content' );
			var item = window.tour[ tourName ][ nextItem ];
			jQuery( '.pulse-wrapper' ).removeClass( 'pulse-border' );
			wrapper.addClass( 'pulse-border' );

			if ( tourEndsHere ) {
				popoverContent += '<br/><br/><a href="" class="close-tour">' + wp.i18n.__( 'Close', 'glotpress' ) + '</a>';
			} else if ( typeof window.tour[ tourName ][ nextItem ] !== 'undefined' && typeof window.tour[ tourName ][ nextItem ].reveal === 'undefined' ) {
				popoverContent += '<div class="popover-nav-btns">';
				popoverContent += showPreviousBtn ? '<br/><br/><a href="" class="tour-button previous-tour-item" data-tourname="' + tourName + '">' + wp.i18n.__( 'Previous', 'glotpress' ) + '</a>' : '';
				popoverContent += '<a href="" class="tour-button next-tour-item button-primary" data-tourname="' + tourName + '">Next</a>';
				popoverContent += '</div>';
			} else if ( typeof window.tour[ tourName ][ nextItem ].reveal !== 'undefined' ) {
				popoverContent += '<br/><br/><a href="" class="reveal-next-tour-item" data-reveal="' + window.tour[ tourName ][ nextItem ].reveal + '">' + wp.i18n.__( 'Reveal Next Step', 'glotpress' ) + '</a>';
			}
			if ( ! tourEndsHere ) {
				popoverContent += '<br/><small><a href="" class="dismiss-tour">' + wp.i18n.__( 'Dismiss this tour', 'glotpress' );
			}
			WebuiPopovers.show( this, { title: window.tour[ tourName ][ 0 ].title, content: popoverContent, width: 300, dismissible: true } );
			jQuery( '.tour-' + tourName ).remove();

			jQuery( '.tour-' + tourName ).remove();
			if ( tourEndsHere ) {
				return;
			}

			addPulse( jQuery( item.selector ), item, tourName, nextItem );
		} );
		function addPulse( field, item, tourName, index ) {
			var div = jQuery( '<div class="pulse-wrapper">' );
			var pulse = jQuery( '<div class="pulse tour-' + tourName + '">' );
			var cssString = '';
			div.data( 'tourname', tourName ).data( 'tourindex', index ).data( 'popover-content', item.html );
			field.wrap( div );
			field.parent().append( pulse );
			if ( typeof item.css !== 'undefined' ) {
				cssString = cssObjectToString( item.css );
				jQuery( item.selector ).closest( '.pulse-wrapper' ).css( 'cssText', cssString );
			}
		}

		// Convert the CSS object to a CSS string
		function cssObjectToString( cssObject ) {
			return jQuery.map( cssObject, function( value, property ) {
				return property + ': ' + value;
			} ).join( '; ' );
		}

		window.tour = gp_tour;
		window.loadTour = function() {
			var color1 = '';
			var color2 = '';
			var sheet = {};
			var n;
			for ( n in window.tour ) {
				color1 = window.tour[ n ][ 0 ].color + '00';
				color2 = window.tour[ n ][ 0 ].color + 'a0';
				sheet = document.styleSheets[ 0 ];
				sheet.insertRule( '@keyframes animation-' + n + ' {' +
					'0% {' +
					'box-shadow: 0 0 0 0 ' + color2 + ';' +
					'}' +
					'70% {' +
					'box-shadow: 0 0 0 10px ' + color1 + ';' +
					'}' +
					'100% {' +
					'box-shadow: 0 0 0 0 ' + color1 + ';' +
					'}' +
					'}',
				sheet.cssRules.length );

				sheet.insertRule( '.tour-' + n + '{' +
					'box-shadow: 0 0 0 ' + color2 + ';' +
					'background: ' + color1 + ';' +
					'-webkit-animation: animation-' + n + ' 2s infinite;' +
					'animation: animation-' + n + ' 2s infinite; }',
				sheet.cssRules.length );
				addPulse( jQuery( window.tour[ n ][ 1 ].selector ), window.tour[ n ][ 1 ], n, 1 );
			}
		};
		window.loadTour();
	}
);
