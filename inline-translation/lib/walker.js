function acceptableNode( node ) {
	if ( null === node.parentElement ) {
		return NodeFilter.FILTER_REJECT;
	}
	if ( [ 'SCRIPT', 'STYLE', 'BODY', 'HTML' ].includes( node.parentElement.nodeName ) ) {
		return NodeFilter.FILTER_REJECT;
	}
	if ( node.parentElement.classList.contains( 'translator-checked' ) ) {
		return NodeFilter.FILTER_REJECT;
	}
	return NodeFilter.FILTER_ACCEPT;
}

module.exports = function( TranslationPair, jQuery, document ) {
	return {
		walkTextNodes: function( origin, callback, finishedCallback ) {
			var node, walker,
				found = true,
				i = 5;

			if ( typeof document === 'object' ) {
				while ( found ) {
					walker = document.createTreeWalker( origin, NodeFilter.SHOW_TEXT, acceptableNode );
					node = walker.currentNode;
					found = false;
					while ( node ) {
						if ( acceptableNode( node ) === NodeFilter.FILTER_REJECT ) {
							// break;
						}
						walk( node );
						found = true;
						node = walker.nextNode();
					}

					if ( --i < 0 ) {
						break;
					}
				}
			} else {
				jQuery( origin ).find( '*' ).contents().filter( function() {
					if ( this.nodeType !== 3 ) {
						return false; // Node.TEXT_NODE
					}
					return acceptableNode( this ) === NodeFilter.FILTER_ACCEPT;
				} ).each( function() {
					walk( this );
				} );
			}

			if ( typeof finishedCallback === 'function' ) {
				finishedCallback();
			}

			function walk( textNode ) {
				var translationPair,
					enclosingNode;
				if ( [ 'SCRIPT', 'STYLE', 'BODY', 'HTML' ].includes( textNode.parentElement.nodeName ) ) {
					return false;
				}
				enclosingNode = jQuery( textNode.parentElement );

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
		},
	};
};
