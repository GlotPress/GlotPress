/* global $gp_translations_options, $gp, toggleWithKeyboard */
/* eslint camelcase: "off" */
jQuery( function( $ ) {
	var $bulkActions = $( '.bulk-action' ),
		$bulkPriority = $( '.bulk-priority' ),
		lastClicked = false;

	$gp.showhide( '#upper-filters-toolbar a.sort', '#upper-filters-toolbar .filters-expanded.sort', {
		show_text: $gp_translations_options.sort + ' &darr;',
		hide_text: $gp_translations_options.sort + ' &uarr;',
		focus: '#sort\\[by\\]\\[original_date_added\\]',
	} );
	$gp.showhide( '#upper-filters-toolbar a.filter', '#upper-filters-toolbar .filters-expanded.filters', {
		show_text: $gp_translations_options.filter + ' &darr;',
		hide_text: $gp_translations_options.filter + ' &uarr;',
		focus: '#filters\\[term\\]',
	} );

	$bulkActions.on( 'change', function() {
		var $optionSelected = $( 'option:selected', this );
		$bulkActions.val( $optionSelected.val() );
		if ( 'set-priority' === $optionSelected.val() ) {
			$bulkPriority.removeClass( 'hidden' );
		} else {
			$bulkPriority.addClass( 'hidden' );
		}
	} );

	$( 'form.bulk-actions' ).submit( function() {
		var	row_ids = $( 'input:checked', $( 'table#translations th.checkbox' ) ).map( function() {
			return $( this ).parents( 'tr.preview' ).attr( 'row' );
		} ).get().join( ',' );
		$( 'input[name="bulk[row-ids]"]', $( this ) ).val( row_ids );
	} );

	( function() {
		var $statusFields = $( '#filter-status-fields' );
		var $checkboxes = $statusFields.find( 'input:checkbox' );
		var $selectedStatus = $( '#filter-status-selected' );

		$( '#filter-status-select-all' ).on( 'click', function() {
			$checkboxes.prop( 'checked', true ).trigger( 'change' );
		} );

		$checkboxes.on( 'change', function() {
			var checkedStatus = $checkboxes.filter( ':checked' ).map( function() {
				return $( this ).val();
			} ).get();

			if ( ! checkedStatus.length ) {
				// Default value used by GP_Translation::for_translation().
				$selectedStatus.val( 'current_or_waiting_or_fuzzy_or_untranslated' );
			} else {
				$selectedStatus.val( checkedStatus.join( '_or_' ) );
			}
		} );
	}() );

	$( 'a#export' ).click( function() {
		var format = $( '#export-format' ).val();
		var what_to_export = $( '#what-to-export' ).val();
		var url = '';
		if ( what_to_export === 'filtered' ) {
			// eslint-disable-next-line vars-on-top
			var separator = ( $( this ).attr( 'filters' ).indexOf( '?' ) === -1 ) ? '?' : '&';
			url = $( this ).attr( 'filters' ) + separator + 'format=' + format;
		} else {
			url = $( this ).attr( 'href' ) + '?format=' + format;
		}
		window.location = url;
		return false;
	} );

	// Check all checkboxes from WP common.js, synced with [25141]
	$( 'tbody' ).children().children( '.checkbox' ).find( ':checkbox' ).click( function( e ) {
		var checks, first, last, checked, sliced;

		if ( 'undefined' === e.shiftKey ) {
			return true;
		}
		if ( e.shiftKey ) {
			if ( ! lastClicked ) {
				return true;
			}
			checks = $( lastClicked ).closest( 'table' ).find( ':checkbox' );
			first = checks.index( lastClicked );
			last = checks.index( this );
			checked = $( this ).prop( 'checked' );
			if ( 0 < first && 0 < last && first !== last ) {
				sliced = ( last > first ) ? checks.slice( first, last ) : checks.slice( last, first );
				sliced.prop( 'checked', function() {
					if ( $( this ).closest( 'tr' ).is( ':visible' ) ) {
						return checked;
					}

					return false;
				} );
			}
		}
		lastClicked = this;
		return true;
	} );

	$( 'thead, tfoot' ).find( '.checkbox :checkbox' ).click( function( e ) {
		var c = $( this ).prop( 'checked' ),
			kbtoggle = 'undefined' === typeof toggleWithKeyboard ? false : toggleWithKeyboard,
			toggle = e.shiftKey || kbtoggle;

		$( this ).closest( 'table' ).children( 'tbody' ).filter( ':visible' )
			.children().children( '.checkbox' ).find( ':checkbox' )
			.prop( 'checked', function() {
				if ( $( this ).closest( 'tr' ).is( ':hidden' ) ) {
					return false;
				}
				if ( toggle ) {
					return $( this ).prop( 'checked' );
				} else if ( c ) {
					return true;
				}
				return false;
			} );

		$( this ).closest( 'table' ).children( 'thead,  tfoot' ).filter( ':visible' )
			.children().children( '.checkbox' ).find( ':checkbox' )
			.prop( 'checked', function() {
				if ( toggle ) {
					return false;
				} else if ( c ) {
					return true;
				}
				return false;
			} );
	} );
} );
