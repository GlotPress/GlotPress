/* global clearTimeout, document, DOMParser, setTimeout, wp */
var $gp = (
	function( $ ) {
		return {
			notices: {
				element: null,
				progressTimer: null,
				init: function() {
					// Both the editor and the glossary initialize $gp on the same page.
					if ( $gp.notices.element ) {
						return;
					}

					// Set here rather than in the markup, so headers of themes that override the template get it too.
					$gp.notices.element = $( '#gp-js-message' ).attr( 'popover', 'manual' );
					$gp.notices.element.on( 'click', '.gp-js-message-dismiss', $gp.notices.clear );
					$gp.notices.element.on( 'animationend', function( event ) {
						// Only hides it, as clear() would also cancel a progress message that is about to show up.
						if ( 'gp-js-message-out' === event.originalEvent.animationName ) {
							$gp.notices.hide();
						}
					} );

					/*
					 * Listens in the capture phase, so that while a message waits to be dismissed, Escape in the editor
					 * closes it instead of also cancelling the translation being edited.
					 */
					document.addEventListener( 'keydown', function( event ) {
						if ( 'Escape' !== event.key || event.isComposing || ! $gp.notices.element.find( '.gp-js-message-dismiss' ).length ) {
							return;
						}

						$gp.notices.clear();

						if ( $( event.target ).closest( '.editor' ).length ) {
							event.preventDefault();
							event.stopPropagation();
						}
					}, true );
				},
				error: function( message ) {
					$gp.notices.genericMessage( 'gp-js-error', message, true );
					$gp.notices.speak( message, 'assertive' );
				},
				notice: function( message ) {
					$gp.notices.genericMessage( 'gp-js-notice', message, true );
					$gp.notices.speak( message, 'polite' );
				},
				success: function( message ) {
					$gp.notices.genericMessage( 'gp-js-success', message, false );
					$gp.notices.speak( message, 'polite' );
				},
				progress: function( message ) {
					clearTimeout( $gp.notices.progressTimer );

					// Most requests answer faster, so their result replaces the message before it would flash up.
					$gp.notices.progressTimer = setTimeout( function() {
						$gp.notices.genericMessage( 'gp-js-notice', message, false );
						$gp.notices.speak( message, 'polite' );
					}, 500 );
				},
				clear: function() {
					clearTimeout( $gp.notices.progressTimer );
					$gp.notices.hide();
					$gp.notices.element.html( '' );
				},
				genericMessage: function( cssClass, message, dismissable ) {
					clearTimeout( $gp.notices.progressTimer );

					$gp.notices.hide();
					$gp.notices.element
						.attr( 'class', 'gp-js-message ' + cssClass )
						.html( '<div id="gp-js-message-content" class="gp-js-message-content">' + message + '</div>' );

					if ( true === dismissable ) {
						$( '<button type="button" class="gp-js-message-dismiss"><svg aria-hidden="true" focusable="false" width="12" height="12" viewBox="0 0 12 12"><path d="M1 1l10 10M11 1L1 11" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg></button>' )
							.attr( 'aria-label', wp.i18n.__( 'Dismiss', 'glotpress' ) )
							.appendTo( $gp.notices.element );
					}

					// Reading the layout lets the browser see the message hidden, so its animations start over when it replaces another one.
					if ( $gp.notices.element.length ) {
						$gp.notices.element.get( 0 ).getBoundingClientRect();
					}

					$gp.notices.show();
				},
				// The message is hidden until it's shown, which screen readers don't reliably announce as a live region.
				speak: function( message, politeness ) {
					var body = new DOMParser().parseFromString( message, 'text/html' ).body;

					// Keeps the items of a list from running together into one word.
					$( 'li', body ).append( ' ' );

					wp.a11y.speak( body.textContent, politeness );
				},
				// The class carries the styles, the popover only lifts the message above everything else where supported.
				show: function() {
					var element = $gp.notices.element.addClass( 'is-open' ).get( 0 );

					if ( element && element.showPopover ) {
						element.showPopover();
					}
				},
				hide: function() {
					var element = $gp.notices.element.removeClass( 'is-open' ).get( 0 );

					if ( element && element.hidePopover && element.matches( ':popover-open' ) ) {
						element.hidePopover();
					}
				},
			},
			esc_html: function( s ) {
				return $( '<div/>' ).text( s ).html();
			},
			init: function() {
				$gp.notices.init();
			},
		};
	}( jQuery )
);

$gp.showhide = (
	function( $ ) {
		return function( link, container, userOptions ) {
			var defaultOptions = {
				show_text: 'Show',
				hide_text: 'Hide',
				focus: false,
				group: 'default',
			};
			var options = $.extend( {}, defaultOptions, userOptions );
			var $link = $( link );
			var $container = $( container );
			var registry;
			if ( ! $gp.showhide.registry[ options.group ] ) {
				$gp.showhide.registry[ options.group ] = [];
			}
			registry = $gp.showhide.registry[ options.group ];
			// eslint-disable-next-line vars-on-top
			var show = function() {
				// eslint-disable-next-line vars-on-top
				for ( var i = 0; i < registry.length; ++i ) {
					registry[ i ].hide();
				}
				$container.show();
				if ( options.focus ) {
					$( options.focus, $container ).focus();
				}
				$link.html( options.hide_text ).addClass( 'open' );
			};
			// eslint-disable-next-line vars-on-top
			var hide = function() {
				$container.hide();
				$link.html( options.show_text ).removeClass( 'open' );
			};
			registry.push( { show: show, hide: hide } );
			$link.click( function() {
				if ( $container.is( ':visible' ) ) {
					hide();
				} else {
					show();
				}
				return false;
			} );
		};
	}( jQuery )
);
$gp.showhide.registry = {};
