/* global wp, document, window, WebuiPopovers, gp_tour */
/* eslint camelcase: "off" */

jQuery( document ).ready(
	function() {
		jQuery( document ).on( 'click', '.close-tour, .dismiss-tour', function() {
			var tourName = jQuery( this ).data( 'tourname' );
			jQuery( this ).closest( '.webui-popover' ).hide();
			jQuery( '.pulse-wrapper' ).removeClass( 'pulse-border' );
			jQuery( '.tour-' + tourName ).remove();
			return false;
		} );
		jQuery( document ).on( 'click', '.next-tour-item', function() {
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
			var currentTourIndex = wrapper.data( 'tourindex' );
			var nextItem = 1 + currentTourIndex;
			var tourEndsHere = typeof window.tour[ tourName ][ nextItem ] === 'undefined';
			var showPreviousBtn = currentTourIndex > 1;
			var popoverContent = wrapper.data( 'popover-content' );
			var item;
			var istopOffsetDefined = typeof window.tour[ tourName ][ currentTourIndex ].topOffset !== 'undefined' && Number.isInteger( window.tour[ tourName ][ currentTourIndex ].topOffset );
			var popoverTopOffset = istopOffsetDefined ? window.tour[ tourName ][ currentTourIndex ].topOffset : 0;
			var isPlacementDefined = typeof window.tour[ tourName ][ currentTourIndex ].placement !== 'undefined';
			var popoverPlacement = isPlacementDefined ? window.tour[ tourName ][ currentTourIndex ].placement : 'bottom-right';
			if ( ! tourEndsHere ) {
				// Check if the selector for the next item does not exists
				if ( jQuery( window.tour[ tourName ][ nextItem ].selector + ':visible' ).length < 1 ) {
					while ( nextItem < window.tour[ tourName ].length ) {
						if ( jQuery( window.tour[ tourName ][ nextItem ].selector + ':visible' ).length > 0 ) {
							break;
						}
						nextItem++;
					}
				}
			}

			item = window.tour[ tourName ][ nextItem ];

			jQuery( '.pulse-wrapper' ).removeClass( 'pulse-border' );
			jQuery( '.webui-popover:visible' ).hide();
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
				popoverContent += '<br/><small><a href="" class="dismiss-tour" data-tourname="' + tourName + '">' + wp.i18n.__( 'Dismiss this tour', 'glotpress' );
			}
			WebuiPopovers.show( this, { title: window.tour[ tourName ][ 0 ].title, content: popoverContent, width: 300, dismissible: true, offsetTop: popoverTopOffset, placement: popoverPlacement } );
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
			var styleElement = document.createElement( 'style' );
			var n;
			var style;

			document.head.appendChild( styleElement );
			style = styleElement.sheet;

			for ( n in window.tour ) {
				color1 = window.tour[ n ][ 0 ].color + '00';
				color2 = window.tour[ n ][ 0 ].color + 'a0';

				style.insertRule( '@keyframes animation-' + n + ' {' +
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
				style.cssRules.length );

				style.insertRule( '.tour-' + n + '{' +
					'box-shadow: 0 0 0 ' + color2 + ';' +
					'background: ' + color1 + ';' +
					'-webkit-animation: animation-' + n + ' 2s infinite;' +
					'animation: animation-' + n + ' 2s infinite; }',
				style.cssRules.length );
				addPulse( jQuery( window.tour[ n ][ 1 ].selector ), window.tour[ n ][ 1 ], n, 1 );
			}
		};
		window.loadTour();
	}
);
