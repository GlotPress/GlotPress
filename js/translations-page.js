jQuery(function($) {
	$gp.showhide('#upper-filters-toolbar a.sort', '#upper-filters-toolbar dl.sort', {
		show_text: 'Sort &darr;',
		hide_text: 'Sort &uarr;', 
		focus: '#sort\\[by\\]'
	});
	$gp.showhide('#upper-filters-toolbar a.filter', '#upper-filters-toolbar dl.filters', {
		show_text: 'Filter &darr;',
		hide_text: 'Filter &uarr;',
		focus: '#filters\\[term\\]'
	});
	$gp.showhide('#upper-filters-toolbar a.bulk', '#upper-filters-toolbar dl.bulk-actions', {
		show_text: 'Bulk &darr;',
		hide_text: 'Bulk &uarr;',
		focus: '#filters\\[term\\]'
	});

	var bulk_dl = $('.filters-toolbar dl.bulk-actions');
	var submits = $('input[type=submit]', bulk_dl);
	

	// make the whole table cell, containing the checkbox clickable
	$('table#translations').on('click', 'td.checkbox', function (e) {
		if ($(e.target).is('input')) return true;
		var cb = this.getElementsByTagName('input')[0];
		cb.checked = !cb.checked;
		$(cb).change();
	});
	
	var rows_checked = 0;

	$('#upper-filters-toolbar a.bulk').click(function() {
		rows_checked = $('input:checked', $('table#translations td.checkbox')).length;
		change_row_checked(0);
	});

	
	var change_row_checked = function(num) {
		rows_checked += num;
		submits.prop('disabled', ! rows_checked);
	}
	
	$(':checkbox', $('table#translations td.checkbox')).each(function() {
		$(this).change(function() {
			this.checked? change_row_checked(+1) : change_row_checked(-1);
		});
	});
	

	var set_all = function (value) {
		$(':checkbox', $('table#translations td.checkbox')).each(function() {
			if ( !this.checked && value) change_row_checked(+1);
			if ( this.checked && !value) change_row_checked(-1);
			this.checked = value;
		});
	}
	
	$('.filters-toolbar dl.bulk-actions a.all').click( function() {
		set_all(true);
	});
	$('.filters-toolbar dl.bulk-actions a.none').click( function() {
		set_all(false);
	});
	
	submits.prop('disabled', true);
	
	$('form.filters-toolbar').submit(function(e) {
		if ($('input[name=approve]', bulk_dl).is(':visible')) {
			this.method = 'post';
			this.action = $gp_translations_options.action;
			var	row_ids = $('input:checked', $('table#translations td.checkbox')).map(function() {
				return $(this).parents('tr.preview').attr('row');
			}).get().join(',');
			$('input[name="bulk[row-ids]"]', $(this)).val(row_ids);
		} else {
			// do not litter the GET form with the long redirect_to
			$('input[name^="bulk"]', $(this)).remove();
		}
		return true;
	});
	
	$('a#export').click(function() {
		var format = $('#export-format').val();
		var what_to_export = $('#what-to-export').val();
		var url = '';
		if (what_to_export == 'filtered') {
			var separator = ( $(this).attr('filters').indexOf('?') == -1 )? '?' : '&';
			url = $(this).attr('filters') + separator + 'format='+format;
		} else {
			url = $(this).attr('href') + '?format='+format;
		}
		window.location = url;
		return false;
	});
});
