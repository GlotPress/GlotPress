//Load Keymaster
s = document.createElement('script');
s.src = chrome.extension.getURL('keymaster.js');
s.type = 'text/javascript';
s.onload = function () {
  this.parentNode.removeChild(this);
  //Load GlotDict
  s = document.createElement('script');
  s.src = chrome.extension.getURL('glotdict.js');
  s.type = 'text/javascript';
  s.onload = function () {
	this.parentNode.removeChild(this);
  };
  (document.head || document.documentElement).appendChild(s);
};
(document.head || document.documentElement).appendChild(s);

