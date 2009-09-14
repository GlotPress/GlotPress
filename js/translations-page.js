// TODO: hide other containers in the same group on show
$gp.showhide = function($) { return function(link, show_text, hide_text, container, focus) {
	link = $(link);
	container = $(container);
	var on_show = function() {
		container.show();
		if (focus) $(focus, container).focus();
		link.html(hide_text).addClass('open');
	}
	var on_hide = function() {
		container.hide();
		link.html(show_text).removeClass('open');
	}
	link.toggle(on_show, on_hide);
}}(jQuery);

jQuery(function($) {
	$gp.showhide('#upper-filters-toolbar a.sort', 'Sort &darr;', 'Sort &uarr;', '#upper-filters-toolbar dl.sort', '#sort\\[by\\]');
	$gp.showhide('#upper-filters-toolbar a.filter', 'Filter &darr;', 'Filter &uarr;', '#upper-filters-toolbar dl.filters', '#filters\\[term\\]');
	$gp.showhide('#upper-filters-toolbar a.bulk', 'Bulk &darr;', 'Bulk &uarr;', '#upper-filters-toolbar dl.bulk-actions', '#filters\\[term\\]');

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
	approve_submit.attr('disabled', 'disabled');
	$('input[name=bulk]', bulk_dl).change(function() {
		approve_submit.removeAttr('disabled');
		approve_submit.attr('value', this.id.match('bulk\\[approve')? 'Approve' : 'Reject');
	});
	
	// 
});


