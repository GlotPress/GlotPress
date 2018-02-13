jsScripts = ['jquery.bind-first', 'dompurify', 'keymaster', 'glotdict-functions', 'glotdict-settings', 'glotdict-hotkey', 'glotdict-validation', 'glotdict-column', 'glotdict'];

script(jsScripts);

function script(url) {
  if (Array.isArray(url)) {
	var self = this, prom = [];
	url.forEach(function (item) {
	  prom.push(self.script(item));
	});
	return Promise.all(prom);
  }
  return new Promise(function (resolve, reject) {
	var r = false,
			t = document.getElementsByTagName("script")[0],
			s = document.createElement("script");
	s.type = "text/javascript";
	s.src = chrome.extension.getURL('js/' + url + '.js');
	s.async = false;
	s.onload = s.onreadystatechange = function () {
	  if (!r && (!this.readyState || this.readyState === "complete")) {
		r = true;
		resolve(this);
	  }
	};
	s.onerror = s.onabort = reject;
	t.parentNode.insertBefore(s, t);
  });
}
// Add the icon
t = document.getElementsByTagName("header")[0],
		s = document.createElement("img");
s.src = chrome.extension.getURL('icons/icon-16.png');
s.style.display = 'none';
s.classList.add('gd_icon');
t.parentNode.insertBefore(s, t);