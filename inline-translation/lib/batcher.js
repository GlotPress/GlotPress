/*
 * This is a utility function to help reduce the number of calls made to
 * the GlotPress database ( or generic backend ), especially as we're
 * loading a new page.
 * It takes a function that takes an array and callback, and generates a new
 * function that takes a single argument and returns a Deferred object.
 * i.e.
 * function ( arrayArgument, callback )
 * to:
 * function ( singleArgument ) { return jQuery.deferred( singleResult ) }
 *
 * Internally, the function collects up a series of these singleArgument
 * calls and makes a single call to the original function ( presumably the
 * backend ) after a brief delay.
 */
var debug = require( 'debug' )( 'automattic:community-translator' );


function handleBatchedResponse( response, originalToCallbacksMap ) {
	var i, data, j, key;
	if ( 'undefined' === typeof response ) {
		return false;
	}

	if ( 'undefined' === typeof response[ 0 ] ) {
		response = [ response ];
	}

	for ( i = 0; ( data = response[ i ] ); i++ ) {
		if ( 'undefined' === typeof data || 'undefined' === typeof data.original ) {
			// if there is not a single valid original
			break;
		}

		key = data.original.singular;
		if ( 'undefined' !== typeof data.original.context && data.original.context ) {
			key = data.original.context + '\u0004' + key;
		}

		if ( 'undefined' === typeof originalToCallbacksMap[ key ] || !
				originalToCallbacksMap[ key ] ) {
			continue;
		}

		for ( j = 0; j < originalToCallbacksMap[ key ].length; j++ ) {
			originalToCallbacksMap[ key ][ j ].resolve( data );
		}

		originalToCallbacksMap[ key ] = null;
		delete originalToCallbacksMap[ key ];
	}

	// reject any keys that have not been handled
	for ( key in originalToCallbacksMap ) {
		if ( ! originalToCallbacksMap[ key ] ) {
			continue;
		}

		for ( j = 0; j < originalToCallbacksMap[ key ].length; j++ ) {
			originalToCallbacksMap[ key ][ j ].reject();
		}
	}
}

module.exports = function( functionToWrap, options ) {
	var batchDelay = 200,
		originalToCallbacksMap = {},
		batchedOriginals = [],
		batchTimeout,
		hash;

	if ( 'function' !== typeof ( functionToWrap ) ) {
		debug(
			'batcher expects the first argument to be a function that takes an array and a callback, got ',
			functionToWrap );
		return null;
	}

	// Functions that need to close over state
	hash = function( original ) {
		var key = original.singular;
		if ( 'undefined' !== typeof original.context ) {
			key = original.context + '\u0004' + key;
		}
		return key;
	};

	delayMore = function() {
		if ( batchTimeout ) {
			clearTimeout( batchTimeout );
		}
		batchTimeout = setTimeout( resolveBatch, batchDelay );
	};

	// Actually make the call through the original function
	resolveBatch = function() {
		// Capture the data relevant to this request
		var originals = batchedOriginals.slice(),
			callbacks = originalToCallbacksMap;

		// Then clear out the data so it's ready for the next batch.
		batchTimeout = null;
		originalToCallbacksMap = {};
		batchedOriginals = [];

		if ( 0 === originals.length ) {
			return;
		}

		functionToWrap( originals, function( response ) {
			handleBatchedResponse( response, callbacks );
		} );
	};

	if ( options ) {
		if ( options.batchDelay ) {
			batchDelay = options.batchDelay;
		}
		if ( options.hash ) {
			hash = options.hash;
		}
	}

	return function( original ) {
		var deferred = new jQuery.Deferred(),
			key = hash( original );

		if ( key in originalToCallbacksMap ) {
			originalToCallbacksMap[ key ].push( deferred );
		} else {
			batchedOriginals.push( original );
			originalToCallbacksMap[ key ] = [ deferred ];
		}

		delayMore();

		return deferred;
	};
};
