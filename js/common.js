var $gp = function($) { return {
	notices: {
		element: null,
		init: function() {
			$gp.notices.element = $('#gp-js-message');
		},
		error: function(message) {
			$gp.notices.generic_message('gp-js-error', message);
		},
		notice: function(message) {
			$gp.notices.generic_message('gp-js-notice', message);
		},
		success: function(message) {
			$gp.notices.generic_message('gp-js-success', message);
			$gp.notices.element.fadeOut(10000);
		},
		clear: function(message) {
			$gp.notices.element.html('').hide();
		},
		generic_message: function(css_class, message) {
			// TODO: add close button, at least to errors
			$gp.notices.element.removeClass().addClass(css_class).html(message);
			$gp.notices.center();
			$gp.notices.element.show();
		},
		center: function() {
			$gp.notices.element.css('left', ($(document).width() - $gp.notices.element.width()) / 2);
		}
	},
	esc_html: function(s) {
		return $('<div/>').text(s).html();
	},
	init: function() {
		$gp.notices.init();
	}
}}(jQuery);

$gp.showhide = function($) { return function(link, show_text, hide_text, container, focus) {
	link = $(link);
	container = $(container);
	var show = function() {
		for(var i=0; i<$gp.showhide.registry.length; ++i) {
			$gp.showhide.registry[i].hide();
		}
		container.show();
		if (focus) $(focus, container).focus();
		link.html(hide_text).addClass('open');
	}
	var hide = function() {
		container.hide();
		link.html(show_text).removeClass('open');
	}
	$gp.showhide.registry.push({show: show, hide: hide});
	link.click(function() {
		container.is(':visible')? hide() : show();
		return false;
	})
}}(jQuery);
$gp.showhide.registry = [];
