jQuery(function($) {
	$gp.showhide('#upper-filters-toolbar a.sort', 'Sort &darr;', 'Sort &uarr;', '#upper-filters-toolbar dl.sort', '#sort\\[by\\]');
	$gp.showhide('#upper-filters-toolbar a.filter', 'Filter &darr;', 'Filter &uarr;', '#upper-filters-toolbar dl.filters', '#filters\\[term\\]');
	$gp.showhide('#upper-filters-toolbar a.bulk', 'Bulk &darr;', 'Bulk &uarr;', '#upper-filters-toolbar dl.bulk-actions', '#filters\\[term\\]');
	$('#bulk\\[action\\]\\[approve-selected\\]').change(function() { $gp.editor.hide(); });
	$('#bulk\\[action\]\\[reject-selected\\]').change(function() { $gp.editor.hide(); });

	var checkbox_cells = $('table#translations td.checkbox');
	var bulk_dl = $('.filters-toolbar dl.bulk-actions');

	// make the whole table cell, containing the checkbox clickable
	checkbox_cells.click( function (e) {
		if ($(e.target).is('input')) return true;
		var cb = this.getElementsByTagName('input')[0];
		cb.checked = !cb.checked;
	});

	var set_all = function (value) {
		$(':checkbox', checkbox_cells).each(function() {
			this.checked = value;
		});
	}
	
	$('a.all', bulk_dl).click(function() {
		set_all(true);
	});
	$('a.none', bulk_dl).click(function() {
		set_all(false);
	});
	
	var approve_submit = $('input[name=approve]', bulk_dl);
	var radios = $('input[name=bulk\[action\]]', bulk_dl);
	approve_submit.attr('disabled', 'disabled');
	radios.change(function() {
		approve_submit.removeAttr('disabled');
		approve_submit.attr('value', this.id.match('bulk\\[action\\]\\[approve')? 'Approve' : 'Reject');
	});
	
	$('form.filters-toolbar').submit(function(e) {
		if (approve_submit.is(':visible')) {
			this.method = 'post';
			this.action = $gp_translations_options.action;
			var checkbox_filter = radios.filter(':checked').val().match('-selected')? 'input:checked' : 'input';
			var	translation_ids = $(checkbox_filter, checkbox_cells).map(function() {
				return $(this).parents('tr.preview').attr('row').split('-')[1];
			}).get().join(',');
			$('input[name=bulk\[translation-ids\]]', $(this)).val(translation_ids);
		} else {
			// do not litter the GET form with the long redirect_to
			$('input[name^=bulk]', $(this)).remove();
		}
		return true;
	});
	
	// 
});


