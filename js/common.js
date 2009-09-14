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
			$gp.notices.element.fadeOut(1500);
			$gp.notices.clear();
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
	init: function() {
		$gp.notices.init();
	}
}}(jQuery);