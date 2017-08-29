var $gp = function($) { return {
	notices: {
		element: null,
		init: function() {
			$gp.notices.element = $('#gp-js-message');
			$gp.notices.element.on( 'click', $gp.notices.clear );
		},
		error: function(message) {
			$gp.notices.generic_message('gp-js-error', message, true);
		},
		notice: function(message) {
			$gp.notices.generic_message('gp-js-notice', message, true);
		},
		success: function(message) {
			$gp.notices.generic_message('gp-js-success', message, false);
			$gp.notices.element.fadeOut(10000);
		},
		clear: function(message) {
			$gp.notices.element.html('').hide();
		},
		generic_message: function(css_class, message, dismiss) {
			if ( true == dismiss ) {
				dismiss_message = '<div id="gp-js-message-dismiss" class="gp-js-message-dismiss">Discard</div>';
			} else {
				dismiss_message = '';
			}
			
			$gp.notices.element.removeClass().addClass('gp-js-message').addClass(css_class).html( '<div id="gp-js-message-content" class="gp-js-message-content">' + message + dismiss_message + '</div>');
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

$gp.showhide = function($) { return function(link, container, options) {
	var defaults= {
		show_text: 'Show',
		hide_text: 'Hide',
		focus: false,
		group: 'default'
	}
	var options = $.extend({}, defaults, options);
	var $link = $(link);
	var $container = $(container);
	if ( !$gp.showhide.registry[options.group] ) $gp.showhide.registry[options.group] = [];
	var registry = $gp.showhide.registry[options.group]; 
	var show = function() {
		for(var i = 0; i < registry.length; ++i) {
			registry[i].hide();
		}
		$container.show();
		if (options.focus) $(options.focus, $container).focus();
		$link.html(options.hide_text).addClass('open');
	}
	var hide = function() {
		$container.hide();
		$link.html(options.show_text).removeClass('open');
	}
	registry.push({show: show, hide: hide});
	$link.click(function() {
		$container.is(':visible')? hide() : show();
		return false;
	})
}}(jQuery);
$gp.showhide.registry = {};
