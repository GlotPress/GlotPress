
/* global window, $gp, document */

/*
	These are the general hotkeys.
	The editor hotkeys are present in the assets/js/editor.js file
 */
jQuery( document ).ready( function( $ ) {
	var dialogForm;
	$.noConflict();
	$( 'html' ).keydown( function( e ) {
		var previousPage, nextPage, firstEditorRow;
		if ( 191 === e.keyCode ) { // Question mark (?) = Show the help with all the hotkeys.
			$( '#keyboard-shortcuts-container' ).css( 'display', 'block' );
			return false;
		}
		if ( 27 === e.keyCode ) { // Escape = Hide the help with all the hotkeys.
			$( '#keyboard-shortcuts-container' ).css( 'display', 'none' );
			return false;
		}
		if ( 37 === e.keyCode && e.altKey ) { // Alt-Left Arrow or Option-Left Arrow = Move to the previous page.
			previousPage = $( '.gp-table-actions.top' ).find( '.previous' );
			if ( ( previousPage.length > 0 ) && ( undefined !== previousPage.attr( 'href' ) ) ) {
				window.location.href = previousPage.attr( 'href' );
			}
			return false;
		}
		if ( 39 === e.keyCode && e.altKey ) { // Alt-Right Arrow or Option-Right Arrow = Move to the next page.
			nextPage = $( '.gp-table-actions.top' ).find( '.next' );
			if ( ( nextPage.length > 0 ) && ( undefined !== nextPage.attr( 'href' ) ) ) {
				window.location.href = nextPage.attr( 'href' );
			}
			return false;
		}
		if ( 49 === e.keyCode && e.altKey ) { // Alt-1 or Option-1 = Show the editor for the first translation in the table.
			e.preventDefault();
			firstEditorRow = $( 'table > tbody  > tr:nth-child(2)' );
			if ( firstEditorRow.length > 0 ) {
				$gp.editor.show( firstEditorRow );
			}
			return false;
		}
		return true;
	} );

	dialogForm = '<div id="keyboard-shortcuts-container" class="keyboard-shortcuts-container">' +
		'<div id="keyboard-shortcuts-popover" class="keyboard-shortcuts-popover">' +
		'<div id="keyboard-shortcuts-popover-title" class="keyboard-shortcuts-popover-title">Keyboard shortcuts</a>' +
		'<button id="keyboard-shortcuts-popover-close-button" class="keyboard-shortcuts-popover-close-button"></button> ' +
		'</div>' +
		'<div>' +
		'<table id="keyboard-shortcuts-table-global-hotkeys" class="keyboard-shortcuts-table-global-hotkeys">' +
		'<tr><th colspan="2">Global hotkeys</th><tr>' +
		'<tr><td><strong>Keyboard shorcut</strong></td><td><strong>Description</strong></td></tr>' +
		'<tr><td><kbd>?</kbd></td><td>Show this help</td></tr>' +
		'<tr><td><kbd>Esc</kbd></td><td>Hide this help</td></tr>' +
		'<tr><td><kbd>Alt</kbd>+<kbd>Left Arrow</kbd> (Windows/Linux)<br><kbd>Option</kbd>+<kbd>Left Arrow</kbd> (macOS)</td><td>Move to the previous page</td></tr>' +
		'<tr><td><kbd>Alt</kbd>+<kbd>Right Arrow</kbd> (Windows/Linux)<br><kbd>Option</kbd>+<kbd>Right Arrow</kbd> (macOS)</td><td>Move to the next page</td></tr>' +
		'</table>' +
		'</div>' +
		'</div>' +
		'</div>';
	$( 'body' ).append( dialogForm );

	$( '#keyboard-shortcuts-popover-close-button' ).click( function() {
		$( '#keyboard-shortcuts-container' ).css( 'display', 'none' );
	} );
} );

