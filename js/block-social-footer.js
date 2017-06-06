var browserInUse = chrome;
if (typeof browserInUse === 'undefined') {
  browserInUse = browser;
}
browserInUse.webRequest.onBeforeRequest.addListener(
		function (requestDetails) {
		  if (requestDetails.originUrl.indexOf("translate.wordpress.org") !== -1) {
			var blackList = [
			  'platform.twitter.com',
			  'facebook.com/plugins',
			  'quantserve.com',
			  'apis.google.com/js/platform.js'
			];
			blackList.forEach(function (element) {
			  if (requestDetails.url.indexOf(element) !== -1) {
				return {
				  cancel: true
				};
			  }
			});

			return {};
		  }
		},
		{
		  urls: ['<all_urls>']
		},
		['requestHeaders', 'blocking']
		);