//Load Keymaster
s = document.createElement('script');
s.src = chrome.extension.getURL('keymaster.js');
s.type = 'text/javascript';
s.onload = function () {
  //Load pluralize
  s1 = document.createElement('script');
  s1.src = chrome.extension.getURL('pluralize.js');
  s1.type = 'text/javascript';
  s1.onload = function () {
    //Load GlotDict
    s2 = document.createElement('script');
    s2.src = chrome.extension.getURL('glotdict.js');
    s2.type = 'text/javascript';
    (document.head || document.documentElement).appendChild(s2);
  };
  (document.head || document.documentElement).appendChild(s1);
};
(document.head || document.documentElement).appendChild(s);