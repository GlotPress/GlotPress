//Set the path of dictionaries
var s = document.createElement('script');
s.textContent = 'var glotdict_path = "' + chrome.extension.getURL("dictionaries") + '"';
(document.head||document.documentElement).appendChild(s);

//Load GlotDict
var s = document.createElement('script');
s.src = chrome.extension.getURL('glotdict.js');
s.type = 'text/javascript';
s.onload = function() {
    this.parentNode.removeChild(this);
};
(document.head || document.documentElement).appendChild(s); 

