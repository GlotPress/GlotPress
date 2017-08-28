// Only for Firefox because in Chome there isn't originURL
if (typeof browser !== 'undefined') {
  browser.webRequest.onBeforeRequest.addListener(
		  function (requestDetails) {
			if (requestDetails.originUrl.indexOf("translate.wordpress.org") !== -1) {
				return {
				  cancel: true
				};
			  }
		  },
		  {
			urls: ["https://platform.twitter.com/*", "https://www.facebook.com/plugins*", "https://apis.google.com/js/platform.js", "https://*.quantserve.com/*",],
			types: ['script','sub_frame']
		  },
		  ['blocking']
		  );
} 