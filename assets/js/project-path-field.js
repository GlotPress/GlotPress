/* global document, window */
document.addEventListener( 'DOMContentLoaded', function() {
	document.querySelectorAll( 'input[data-gp-project-search]' ).forEach( function( input ) {
		var list = document.getElementById( input.getAttribute( 'list' ) );
		var timer, controller;

		input.addEventListener( 'input', function() {
			window.clearTimeout( timer );

			// Waits for a pause in typing, so not every key press sends a search.
			timer = window.setTimeout( function() {
				var query = input.value.trim();
				var url = new window.URL( input.dataset.gpProjectSearch, document.baseURI );

				if ( controller ) {
					controller.abort();
				}

				if ( ! query ) {
					list.replaceChildren();
					return;
				}

				controller = new window.AbortController();
				url.searchParams.set( 'q', query );

				window.fetch( url, { signal: controller.signal, credentials: 'same-origin' } )
					.then( function( response ) {
						return response.json();
					} )
					.then( function( projects ) {
						list.replaceChildren.apply( list, projects.map( function( project ) {
							var option = document.createElement( 'option' );
							option.value = project.path;
							option.label = project.name;
							return option;
						} ) );
					} )
					// Aborted searches reject too, and a failed one just leaves the previous suggestions.
					.catch( function() {} );
			}, 200 );
		} );
	} );
} );
