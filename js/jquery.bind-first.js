/*
 * jQuery.bind-first library v0.2.3 patched by Mte90
 * Copyright (c) 2013 Vladimir Zhuravlev
 *
 * Released under MIT License
 * @license
 **/

(function ($) {
  function eventsData($el) {
	return $el.data('events');
  }

  function moveHandlerToTop($el, eventName, isDelegated) {
	var data = eventsData($el);
	var events = data[eventName];

	var handler = isDelegated ? events.splice(events.delegateCount - 1, 1)[0] : events.pop();
	events.splice(isDelegated ? 0 : (events.delegateCount || 0), 0, handler);
  }

  function moveEventHandlers($elems, eventsString, isDelegate) {
	var events = eventsString.split(/\s+/);
	$elems.each(function () {
	  for (var i = 0; i < events.length; ++i) {
		var pureEventName = $.trim(events[i]).match(/[^\.]+/i)[0];
		moveHandlerToTop($(this), pureEventName, isDelegate);
	  }
	});
  }

  function makeMethod(methodName) {
	$.fn[methodName + 'First'] = function () {
	  var args = $.makeArray(arguments);
	  var eventsString = args.shift();

	  if (eventsString) {
		$.fn[methodName].apply(this, arguments);
		moveEventHandlers(this, eventsString);
	  }

	  return this;
	};
  }

  makeMethod('bind');
  makeMethod('one');

  $.fn.onFirst = function (types, selector) {
	var $el = $(this);
	var isDelegated = typeof selector === 'string';

	$.fn.on.apply($el, arguments);

	// events map
	if (typeof types === 'object') {
	  for (type in types) {
		if (types.hasOwnProperty(type)) {
		  moveEventHandlers($el, type, isDelegated);
		}
	  }
	} else if (typeof types === 'string') {
	  moveEventHandlers($el, types, isDelegated);
	}

	return $el;
  };

})(jQuery);
