//Load Bind-First
s = document.createElement('script');
s.src = chrome.extension.getURL('jquery.bind-first.js');
s.type = 'text/javascript';
s.onload = function () {
  //Load Keymaster
  s1 = document.createElement('script');
  s1.src = chrome.extension.getURL('keymaster.js');
  s1.type = 'text/javascript';
  s1.onload = function () {
	//Load pluralize
	s2 = document.createElement('script');
	s2.src = chrome.extension.getURL('pluralize.js');
	s2.type = 'text/javascript';
	s2.onload = function () {
	  //Load GlotDict
	  s3 = document.createElement('script');
	  s3.src = chrome.extension.getURL('glotdict.js');
	  s3.type = 'text/javascript';
	  (document.head || document.documentElement).appendChild(s3);
	};
	(document.head || document.documentElement).appendChild(s2);
  };
  (document.head || document.documentElement).appendChild(s1);
};
(document.head || document.documentElement).appendChild(s);