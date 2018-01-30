var $gp = function($) { return {
	notices: {
		element: null,
		init: function() {
			$gp.notices.element = $('#gp-js-message');
			$gp.notices.element.on( 'click', '.gp-js-message-dismiss', $gp.notices.clear );
		},
		error: function( message ) {
			$gp.notices.genericMessage( 'gp-js-error', message, true );
		},
		notice: function( message ) {
			$gp.notices.genericMessage( 'gp-js-notice', message, true );
		},
		success: function( message ) {
			$gp.notices.genericMessage( 'gp-js-success', message, false );
			$gp.notices.element.fadeOut( 10000 );
		},
		clear: function() {
			$gp.notices.element.html( '' ).hide();
		},
		genericMessage: function( cssClass, message, dismissable ) {
			var dismissButton = '';

			// Stop and complete any running animations.
			$gp.notices.element.stop( true, true );

			if ( true === dismissable ) {
				dismissButton = ' <button type="button" class="button-link gp-js-message-dismiss">' + $gp.l10n.dismiss + '</button>';
			}

			$gp.notices.element.removeClass()
				.addClass( 'gp-js-message' )
				.addClass( cssClass )
				.html( '<div id="gp-js-message-content" class="gp-js-message-content">' + message + dismissButton + '</div>' );
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
	var registry;
	if ( !$gp.showhide.registry[options.group] ) $gp.showhide.registry[options.group] = [];
	registry = $gp.showhide.registry[options.group];
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
