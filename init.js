//Load jQuery Hotkeys
s = document.createElement('script');
s.src = chrome.extension.getURL('keymaster.js');
s.type = 'text/javascript';
s.onload = function() {
    this.parentNode.removeChild(this);
};
(document.head || document.documentElement).appendChild(s); 
//Load GlotDict
s = document.createElement('script');
s.src = chrome.extension.getURL('glotdict.js');
s.type = 'text/javascript';
s.onload = function() {
    this.parentNode.removeChild(this);
};
(document.head || document.documentElement).appendChild(s); 
