
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
			$( '#keyboard-shortcuts-container' ).css( 'display', 'flex' );
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
		'' +
		'<tr><td><kbd>Esc</kbd></td><td>Hide this help</td></tr>' +
		'' +
		'<tr><td><kbd>Alt</kbd>+<kbd>Left Arrow</kbd> (Windows/Linux)<br>' +
		'<kbd>Option</kbd>+<kbd>Left Arrow</kbd> (macOS)</td>' +
		'<td>Move to the previous page</td></tr>' +
		'' +
		'<tr><td><kbd>Alt</kbd>+<kbd>Right Arrow</kbd> (Windows/Linux)<br>' +
		'<kbd>Option</kbd>+<kbd>Right Arrow</kbd> (macOS)</td>' +
		'<td>Move to the next page</td></tr>' +
		'</table>' +
		'' +
		'<table id="keyboard-shortcuts-table-editor-hotkeys" class="keyboard-shortcuts-table-editor-hotkeys">' +
		'<tr><th colspan="2">Editor hotkeys</th><tr>' +
		'<tr><td><kbd>Esc</kbd><br>' +
		'<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>Z</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>Z</kbd> (macOS)</td>' +
		'<td>Cancel</td></tr>' +
		'' +
		'<tr><td><kbd>Page Up</kbd><br>' +
		'<kbd>Ctrl</kbd>+<kbd>Up Arrow</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Up Arrow</kbd> (macOS)</td>' +
		'<td>Move to the previous editor</td></tr>' +
		'' +
		'<tr><td><kbd>Page Down</kbd><br>' +
		'<kbd>Ctrl</kbd>+<kbd>Down Arrow</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Down Arrow</kbd> (macOS)</td>' +
		'<td>Move to the next editor</td></tr>' +
		'' +
		'<tr><td><kbd>Shift</kbd>+<kbd>Enter</kbd><br>' +
		'<kbd>Ctrl</kbd>+<kbd>Enter</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Enter</kbd> (macOS)</td>' +
		'<td>Save or suggest the translation</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>B</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>B</kbd> (macOS)<br>' +
		'<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>C</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>C</kbd> (macOS)<br></td>' +
		'<td>Copy from the original</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>+</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>+</kbd> (macOS)<br>' +
		'<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>A</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>A</kbd> (macOS)<br></td>' +
		'<td>Approve the translation</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>-</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>-</kbd> (macOS)<br>' +
		'<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>R</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>R</kbd> (macOS)<br></td>' +
		'<td>Reject the translation</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>~</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>~</kbd> (macOS)<br>' +
		'<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>~</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>~</kbd> (macOS)<br>' +
		'<kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>F</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>F</kbd> (macOS)<br></td>' +
		'<td>Set the translation as fuzzy</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>D</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>D</kbd> (macOS)<br>' +
		'<td>Dismiss validation warnings for the current visible editor</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>D</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>D</kbd> (macOS)<br>' +
		'<td>Dismiss validation warnings for the current page</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>Left Arrow</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Left Arrow</kbd> (macOS)<br>' +
		'<td>Move to the previous page</td></tr>' +
		'' +
		'<tr><td><kbd>Ctrl</kbd>+<kbd>Right Arrow</kbd> (Windows/Linux)<br>' +
		'<kbd>Cmd</kbd>+<kbd>Right Arrow</kbd> (macOS)<br>' +
		'<td>Move to the next page</td></tr>' +
		'' +
		'</table>' +
		'</div>' +
		'</div>' +
		'</div>';
	$( 'body' ).append( dialogForm );

	$( '#keyboard-shortcuts-popover-close-button' ).click( function() {
		$( '#keyboard-shortcuts-container' ).css( 'display', 'none' );
	} );
} );

