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

$gp.modal = (
	function( $ ) {
		return {
      init: function( ) {
        this.overlay = document.createElement('div');
        this.overlay.className = 'overlay_';
        this.overlay.style.position = 'fixed';
        this.overlay.style.top = 0;
        this.overlay.style.right = 0;
        this.overlay.style.bottom = 0;
        this.overlay.style.left = 0;
        this.overlay.style.zIndex = '99999';
        this.overlay.style.background = 'rgba(0, 0, 0, .5)';
        this.overlay.setAttribute('tabindex', -1);

        this.modalWindow = document.createElement('div');
        this.modalWindow.className = 'modal';
        this.modalWindow.style.position = 'fixed';
        this.modalWindow.style.top = 0;
        this.modalWindow.style.right = 0;
        this.modalWindow.style.bottom = 0;
        this.modalWindow.style.left = 0;
        this.modalWindow.style.width = '80%';
        this.modalWindow.style.height = '30%';
        this.modalWindow.style.margin = 'auto';
        this.modalWindow.style.background = '#EEE';
        this.modalWindow.style.zIndex = '99999';
        this.modalWindow.setAttribute('role', 'dialog');
        this.modalWindow.setAttribute('tabindex', 0);

        this.modalWrapper = document.createElement('div');
        this.modalWrapper.className = 'modal__wrapper';
        this.modalWrapper.style.overflow = 'auto';
        this.modalWrapper.style.height = '100%';

        this.modalContent = document.createElement('div');
        this.modalContent.className = 'modal__content';
        this.modalContent.style.padding = '1em';
        this.modalContent.style.textAlign = 'center';

        this.closeButton = document.createElement('button');
        this.closeButton.className = 'modal__close';
        this.closeButton.style.left = '10px';
        this.closeButton.style.top = '-45px';
        this.closeButton.style.position = 'relative';
        this.closeButton.innerHTML = 'Close';
        this.closeButton.setAttribute('type', 'button');

        this.closeButton.onclick = function() {
          self.close();
        };

        this.modalWindow.appendChild(this.modalWrapper);
        this.modalWrapper.appendChild(this.modalContent);
        this.modalWindow.appendChild(this.closeButton);

        this.isOpen = false;
      },
      open: function(text_modal, callback) {
        if (this.isOpen) {
          return;
        }

        this.modalContent.innerHTML = text_modal;

        this.target.appendChild(this.overlay);
        this.target.appendChild(this.modalWindow);
        this.modalWindow.focus();

        this.isOpen = true;

        if (callback) {
          callback.call(this);
        }
      },
      close: function(callback) {
        this.target.removeChild(this.modalWindow);
        this.target.removeChild(this.overlay);
        this.isOpen = false;

        if (callback) {
          callback.call(this);
        }
      }
    }
  }
)(jQuery);

jQuery( function( $ ) {
	$gp.modal.init();
  $gp.modal.open('Keyboard Shortcuts');
} );