
module.exports = function( TranslationPair, jQuery, document ) {
	return {
		walkTextNodes: function( origin, callback, finishedCallback ) {
			var node, walker;

			if ( typeof document === 'object' ) {
				walker = document.createTreeWalker( origin, NodeFilter.SHOW_TEXT, null, false );

				while ( ( node = walker.nextNode() ) ) {
					walk( node );
				}
			} else {
				jQuery( origin ).find( '*' ).contents().filter( function() {
					return this.nodeType === 3; // Node.TEXT_NODE
				} ).each( function() {
					walk( this );
				} );
			}

			if ( typeof finishedCallback === 'function' ) {
				finishedCallback();
			}

			function walk( textNode ) {
				var translationPair,
					enclosingNode = jQuery( textNode.parentNode );

				if (
						enclosingNode.is( 'script' ) ||
						enclosingNode.hasClass( 'translator-checked' )
				) {
					return false;
				}

				enclosingNode.addClass( 'translator-checked' );

				if (
						enclosingNode.closest( '.webui-popover' ).length
				) {
					return false;
				}

				translationPair = TranslationPair.extractFrom( enclosingNode );

				if ( false === translationPair ) {
					enclosingNode.addClass( 'translator-dont-translate' );
					return false;
				}

				if ( typeof callback === 'function' ) {
					callback( translationPair, enclosingNode );
				}

				return true;
			}
		}
	};
};
