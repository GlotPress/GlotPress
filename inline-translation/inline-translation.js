(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.gpInlineTranslation = f()}})(function(){var define,module,exports;return (function(){function r(e,n,t){function o(i,f){if(!n[i]){if(!e[i]){var c="function"==typeof require&&require;if(!f&&c)return c(i,!0);if(u)return u(i,!0);var a=new Error("Cannot find module '"+i+"'");throw a.code="MODULE_NOT_FOUND",a}var p=n[i]={exports:{}};e[i][0].call(p.exports,function(r){var n=e[i][1][r];return o(n||r)},p,p.exports,r,e,n,t)}return n[i].exports}for(var u="function"==typeof require&&require,i=0;i<t.length;i++)o(t[i]);return o}return r})()({1:[function(require,module,exports){
var debug=require("debug")("automattic:community-translator");function handleBatchedResponse(e,t){var n,o,r,a;if(void 0===e)return!1;for(void 0===e[0]&&(e=[e]),n=0;(o=e[n])&&(void 0!==o&&void 0!==o.original);n++)if(a=o.original.singular,void 0!==o.original.context&&o.original.context&&(a=o.original.context+""+a),void 0!==t[a]&&t[a]){for(r=0;r<t[a].length;r++)t[a][r].resolve(o);t[a]=null,delete t[a]}for(a in t)if(t[a])for(r=0;r<t[a].length;r++)t[a][r].reject()}module.exports=function(e,t){var n,o,r=200,a={},i=[];return"function"!=typeof e?(debug("batcher expects the first argument to be a function that takes an array and a callback, got ",e),null):(o=function(e){var t=e.singular;return void 0!==e.context&&(t=e.context+""+t),t},delayMore=function(){n&&clearTimeout(n),n=setTimeout(resolveBatch,r)},resolveBatch=function(){var t=i.slice(),o=a;n=null,a={},i=[],0!==t.length&&e(t,function(e){handleBatchedResponse(e,o)})},t&&(t.batchDelay&&(r=t.batchDelay),t.hash&&(o=t.hash)),function(e){var t=new jQuery.Deferred,n=o(e);return n in a?a[n].push(t):(i.push(e),a[n]=[t]),delayMore(),t})};

},{"debug":11}],2:[function(require,module,exports){
"use strict";var debug=require("debug")("automattic:community-translator"),batcher=require("./batcher.js");function GlotPress(t){var r={url:"",project:"",translation_set_slug:"default"};function e(t){return t=jQuery.extend({type:"POST",data:{},dataType:"json",xhrFields:{withCredentials:!0},crossDomain:!0},t),jQuery.ajax(t)}function n(t){return r.url+t}return{getPermalink:function(e){var n=e.getOriginal().getId(),s=r.project,a=r.translation_set_slug;e.getGlotPressProject()&&(s=e.getGlotPressProject());var o=r.url+"/projects/"+s+"/"+t.getLocaleCode()+"/"+a+"?filters[original_id]="+n;return o},loadSettings:function(t){void 0!==t.url?r.url=t.url:debug("Missing GP server url"),void 0!==t.url?r.project=t.project:debug("Missing GP project path"),void 0!==t.translation_set_slug&&(r.translation_set_slug=t.translation_set_slug)},queryByOriginal:batcher(function(s,a){e({url:n("/api/translations/-query-by-originals"),data:{project:r.project,translation_set_slug:r.translation_set_slug,locale_slug:t.getLocaleCode(),original_strings:JSON.stringify(s)}}).done(function(t){a(t)})}),submitTranslation:function(s){return e({url:n("/api/translations/-new"),data:{project:r.project,translation_set_slug:r.translation_set_slug,locale_slug:t.getLocaleCode(),translation:s}})}}}module.exports=GlotPress;

},{"./batcher.js":1,"debug":11}],3:[function(require,module,exports){
"use strict";var debounceTimeout,loadCSS,loadData,registerContentChangedCallback,registerDomChangedCallback,registerPopoverHandlers,findNewTranslatableTexts,glotPress,currentUserId,walker,debug=require("debug")("community-translator"),TranslationPair=require("./translation-pair"),Walker=require("./walker"),Locale=require("./locale"),Popover=require("./popover"),GlotPress=require("./glotpress"),WebUIPopover=require("./jquery.webui-popover.js"),currentlyWalkingTheDom=!1,translationData={baseUrl:"/",currentUserId:!1,localeCode:"en",languageName:"English",pluralForms:"nplurals=2; plural=(n != 1)",contentChangedCallback:function(){},glotPress:{url:"http://glotpress.dev",project:"test"}},translationUpdateCallbacks=[];function notifyTranslated(a){debug("Notifying string translated",a.serialize()),translationUpdateCallbacks.forEach(function(t){t(a.serialize())})}function removeCssClasses(){var a=["translator-checked","translator-untranslated","translator-translated","translator-user-translated","translator-untranslatable","translator-dont-translate"];jQuery("."+a.join(", .")).removeClass(a.join(" "))}function unRegisterPopoverHandlers(){jQuery(document).off("submit","form.ct-existing-translation,form.ct-new-translation"),jQuery(".translator-translatable").webuiPopover("destroy")}function makeUntranslatable(a){debug("makeUntranslatable:",a),a.removeClass("translator-untranslated translator-translated translator-translatable translator-checking"),a.addClass("translator-dont-translate")}function makeTranslatable(a,t){a.createPopover(t,glotPress),t.removeClass("translator-checking").addClass("translator-translatable"),a.isFullyTranslated()?a.isTranslationWaiting()?t.removeClass("translator-translated").addClass("translator-user-translated"):t.removeClass("translator-user-translated").addClass("translator-translated"):t.addClass("translator-untranslated")}module.exports={load:function(){if(void 0===window.gpInlineTranslationData)return!1;loadCSS(),loadData(window.gpInlineTranslationData),registerPopoverHandlers(),registerContentChangedCallback(),findNewTranslatableTexts()},unload:function(){debounceTimeout&&clearTimeout(debounceTimeout),"object"==typeof window.gpInlineTranslationData&&(window.gpInlineTranslationData.contentChangedCallback=function(){}),unRegisterPopoverHandlers(),removeCssClasses()},registerTranslatedCallback:function(a){translationUpdateCallbacks.push(a)}},loadCSS=function(){var a=document.createElement("link");a.setAttribute("rel","stylesheet"),a.setAttribute("type","text/css"),a.setAttribute("href",translationData.baseUrl+"community-translator.css"),document.getElementsByTagName("head")[0].appendChild(a),jQuery("iframe").addClass("translator-untranslatable")},loadData=function(a){"object"==typeof a&&"string"==typeof a.localeCode&&(translationData=a),translationData.locale=new Locale(translationData.localeCode,translationData.languageName,translationData.pluralForms),currentUserId=translationData.currentUserId,glotPress=new GlotPress(translationData.locale),void 0!==translationData.glotPress?glotPress.loadSettings(translationData.glotPress):debug("Missing GlotPress settings"),TranslationPair.setTranslationData(translationData),walker=new Walker(TranslationPair,jQuery,document)},registerContentChangedCallback=function(){"object"==typeof window.gpInlineTranslationData&&(debug("Registering translator contentChangedCallback"),window.gpInlineTranslationData.contentChangedCallback=function(){debounceTimeout&&clearTimeout(debounceTimeout),debounceTimeout=setTimeout(findNewTranslatableTexts,250)},"object"==typeof window.gpInlineTranslationData.stringsUsedOnPage&&registerDomChangedCallback())},registerDomChangedCallback=function(){var a=10,t=document.body.innerHTML.length,e=function(){var n;--a<=0||(n=document.body.innerHTML.length,t!==n&&(t=n,debounceTimeout&&clearTimeout(debounceTimeout),debounceTimeout=setTimeout(findNewTranslatableTexts,1700)),setTimeout(e,1500))};setTimeout(e,1500)},registerPopoverHandlers=function(){jQuery(document).on("keyup","textarea.translation",function(){var a,t=jQuery(this).parents("form.ct-new-translation"),e=t.find("textarea"),n=t.find("button");a=e.filter(function(){return this.value.length}),n.prop("disabled",0===a.length)}),jQuery(document).on("submit","form.ct-new-translation",function(){var a=jQuery(this),t=jQuery("."+a.data("nodes")),e=(a.find("textarea").val(),a.data("translationPair")),n=a.find("textarea").map(function(){return jQuery(this).val()}).get();return!!n.every(function(a){return a.trim().length>0})&&(t.addClass("translator-user-translated").removeClass("translator-untranslated"),a.closest(".webui-popover").hide(),jQuery.when(e.getOriginal().getId()).done(function(a){var r=jQuery.makeArray(n),o={};o[a]=r,glotPress.submitTranslation(o).done(function(n){void 0!==n[a]&&(e.updateAllTranslations(n[a],currentUserId),makeTranslatable(e,t),notifyTranslated(e))}).fail(function(){debug("Submitting new translation failed",o)})}).fail(function(){debug("Original cannot be found in GlotPress")}),!1)}),jQuery(document).on("submit","form.ct-existing-translation",function(){var a,t=jQuery(this),e=t.data("translationPair");return"object"!=typeof e?(debug("could not find translation for node",t),!1):(a=new Popover(e,translationData.locale,glotPress),t.parent().empty().append(a.getTranslationHtml()),!1)})},findNewTranslatableTexts=function(){if(currentlyWalkingTheDom)return debounceTimeout&&clearTimeout(debounceTimeout),void(debounceTimeout=setTimeout(findNewTranslatableTexts,1500));currentlyWalkingTheDom=!0,debug("Searching for translatable texts"),walker.walkTextNodes(document.body,function(a,t){t.addClass("translator-checking"),a.fetchOriginalAndTranslations(glotPress,currentUserId).fail(makeUntranslatable.bind(null,t)).done(makeTranslatable.bind(null,a,t))},function(){currentlyWalkingTheDom=!1})};

},{"./glotpress":2,"./jquery.webui-popover.js":4,"./locale":5,"./popover":7,"./translation-pair":8,"./walker":10,"debug":11}],4:[function(require,module,exports){
!function(t,e,i,o){var n="webuiPopover",s={placement:"auto",width:400,height:"auto",trigger:"rightclick",style:"",delay:300,cache:!1,multi:!1,arrow:!0,title:"",content:"",closeable:!1,padding:!0,url:"",type:"html",onload:!1,translationPair:null,template:'<div class="webui-popover"><div class="arrow"></div><div class="webui-popover-inner"><a href="#" class="close">x</a><h3 class="webui-popover-title"></h3><div class="webui-popover-content"><i class="icon-refresh"></i> <p>&nbsp;</p></div></div></div>'};function h(e,i){this.$element=t(e),this.$eventDelegate=this.$element,this.$element.closest("button").length&&(this.$eventDelegate=this.$element.closest("button")),this.options=t.extend({},s,i),this._defaults=s,this._name=n,this.init()}h.prototype={init:function(){"click"===this.options.trigger?this.$eventDelegate.off("click").on("click",t.proxy(this.toggle,this)):"rightclick"===this.options.trigger?(this.$eventDelegate.off("contextmenu").on("contextmenu",t.proxy(this.toggle,this)),this.$eventDelegate.off("click").on("click",function(t){if(t.ctrlKey)return!1})):this.$eventDelegate.off("mouseenter mouseleave").on("mouseenter",t.proxy(this.mouseenterHandler,this)).on("mouseleave",t.proxy(this.mouseleaveHandler,this)),this._poped=!1,this._inited=!0},destroy:function(){this.hide(),this.$element.data("plugin_"+n,null),this.$eventDelegate.off(),this.$target&&this.$target.remove()},hide:function(e){e&&(e.preventDefault(),e.stopPropagation());e=t.Event("hide.webui.popover");this.$element.trigger(e),this.$target&&this.$target.removeClass("in").hide(),this.$element.trigger("hidden.webui.popover")},toggle:function(t){t&&(t.preventDefault(),t.stopPropagation()),this[this.getTarget().hasClass("in")?"hide":"show"](t)},hideAll:function(){t("div.webui-popover").not(".webui-popover-fixed").removeClass("in").hide()},show:function(t){t&&(t.preventDefault(),t.stopPropagation());var e=this.getTarget().removeClass().addClass("webui-popover");if(this.options.multi||this.hideAll(),!this.options.cache||!this._poped){if(this.setTitle(this.getTitle()),this.options.closeable||e.find(".close").off("click").remove(),this.isAsync())return this.setContentASync(this.options.content),void this.displayContent();this.setContent(this.getContent()),e.show()}this.displayContent(),this.bindBodyEvents()},displayContent:function(){var e,o=this.getElementPosition(),n=this.getTarget().removeClass().addClass("webui-popover"),s=this.getContentElement(),h=n[0].offsetWidth,r=n[0].offsetHeight,l=t.Event("show.webui.popover");this.$element.trigger(l),"auto"!==this.options.width&&n.width(this.options.width),"auto"!==this.options.height&&s.height(this.options.height),this.options.arrow||n.find(".arrow").remove(),n.remove().css({top:-1e3,left:-1e3,display:"block"}).appendTo(i.body),h=n[0].offsetWidth,r=n[0].offsetHeight,e=this.getPlacement(o,r),this.initTargetEvents();var a=this.getTargetPositin(o,e,h,r);if(this.$target.css(a.position).addClass(e).addClass("in"),"iframe"===this.options.type){var p=n.find("iframe");p.width(n.width()).height(p.parent().height())}if(this.options.style&&this.$target.addClass("webui-popover-"+this.options.style),this.options.padding||(s.css("height",s.outerHeight()),this.$target.addClass("webui-no-padding")),this.options.arrow||this.$target.css({margin:0}),this.options.arrow){var c=this.$target.find(".arrow");c.removeAttr("style"),a.arrowOffset&&c.css(a.arrowOffset)}this.options.onload&&"function"==typeof this.options.onload&&this.options.onload(s,this.$target),this.options.translationPair&&s.find("form").data("translationPair",this.options.translationPair),this._poped=!0,this.$element.trigger("shown.webui.popover")},isTargetLoaded:function(){return 0===this.getTarget().find("i.glyphicon-refresh").length},getTarget:function(){return this.$target||(this.$target=t(this.options.template)),this.$target},getTitleElement:function(){return this.getTarget().find(".webui-popover-title")},getContentElement:function(){return this.getTarget().find(".webui-popover-content")},getTitle:function(){return this.options.title||this.$element.attr("data-title")||this.$element.attr("title")},setTitle:function(t){var e=this.getTitleElement();t?e.html(t):e.remove()},hasContent:function(){return this.getContent()},getContent:function(){if(this.options.url)"iframe"===this.options.type&&(this.content=t('<iframe frameborder="0"></iframe>').attr("src",this.options.url));else if(!this.content){var e="";e=t.isFunction(this.options.content)?this.options.content.apply(this.$element[0],arguments):this.options.content,this.content=this.$element.attr("data-content")||e}return this.content},setContent:function(t){var e=this.getTarget();this.getContentElement().html(t),this.$target=e},isAsync:function(){return"async"===this.options.type},setContentASync:function(e){var i=this;t.ajax({url:this.options.url,type:"GET",cache:this.options.cache,success:function(o){e&&t.isFunction(e)?i.content=e.apply(i.$element[0],[o]):i.content=o,i.setContent(i.content),i.getContentElement().removeAttr("style"),i.displayContent()}})},bindBodyEvents:function(){t("body").off("keyup.webui-popover").on("keyup.webui-popover",t.proxy(this.escapeHandler,this)),t("body").off("click.webui-popover").on("click.webui-popover",t.proxy(this.bodyClickHandler,this))},mouseenterHandler:function(){this._timeout&&clearTimeout(this._timeout),this.getTarget().is(":visible")||this.show()},mouseleaveHandler:function(){var t=this;t._timeout=setTimeout(function(){t.hide()},t.options.delay)},escapeHandler:function(t){27===t.keyCode&&this.hideAll()},bodyClickHandler:function(t){this.hideAll()},targetClickHandler:function(t){t.stopPropagation()},initTargetEvents:function(){"click"===this.options.trigger||("rightclick"===this.options.trigger?this.$target.find(".close").off("click").on("click",t.proxy(this.hide,this)):this.$target.off("mouseenter mouseleave").on("mouseenter",t.proxy(this.mouseenterHandler,this)).on("mouseleave",t.proxy(this.mouseleaveHandler,this))),this.$target.find(".close").off("click").on("click",t.proxy(this.hide,this)),this.$target.off("click.webui-popover").on("click.webui-popover",t.proxy(this.targetClickHandler,this))},getPlacement:function(t,e){var o,n=i.documentElement,s=i.body,h=n.clientWidth,r=n.clientHeight,l=Math.max(s.scrollTop,n.scrollTop),a=Math.max(s.scrollLeft,n.scrollLeft),p=Math.max(0,t.left-a),c=Math.max(0,t.top-l);return"auto"===(o="function"==typeof this.options.placement?this.options.placement.call(this,this.getTarget()[0],this.$element[0]):this.$element.data("placement")||this.options.placement)&&(p<h/3?o=c<r/3?"bottom-right":c<2*r/3?"right":"top-right":p<2*h/3?o=c<r/3?"bottom":c<2*r/3?"bottom":"top":(o=c>e+20?"top-left":"bottom-left",o=c<r/3?"bottom-left":c<2*r/3?"left":"top-left")),o},getElementPosition:function(){return t.extend({},this.$element.offset(),{width:this.$element[0].offsetWidth,height:this.$element[0].offsetHeight})},getTargetPositin:function(t,e,i,o){var n=t,s=this.$element.outerWidth(),h=this.$element.outerHeight(),r={},l=null,a=this.options.arrow?28:0,p=s<a+10?a:0,c=h<a+10?a:0;switch(e){case"bottom":r={top:n.top+n.height,left:n.left+n.width/2-i/2};break;case"top":r={top:n.top-o,left:n.left+n.width/2-i/2};break;case"left":r={top:n.top+n.height/2-o/2,left:n.left-i};break;case"right":r={top:n.top+n.height/2-o/2,left:n.left+n.width};break;case"top-right":r={top:n.top-o,left:n.left-p},l={left:s/2+p};break;case"top-left":r={top:n.top-o,left:n.left-i+n.width+p},l={left:i-s/2-p};break;case"bottom-right":r={top:n.top+n.height,left:n.left-p},l={left:s/2+p};break;case"bottom-left":r={top:n.top+n.height,left:n.left-i+n.width+p},l={left:i-s/2-p};break;case"right-top":r={top:n.top-o+n.height+c,left:n.left+n.width},l={top:o-h/2-c};break;case"right-bottom":r={top:n.top-c,left:n.left+n.width},l={top:h/2+c};break;case"left-top":r={top:n.top-o+n.height+c,left:n.left-i},l={top:o-h/2-c};break;case"left-bottom":r={top:n.top,left:n.left-i},l={top:h/2}}return{position:r,arrowOffset:l}}},t.fn[n]=function(e){return this.each(function(){var i=t.data(this,"plugin_"+n);i?"destroy"===e?i.destroy():"string"==typeof e&&i[e]():(e?"string"==typeof e?"destroy"!==e&&(i=new h(this,null))[e]():"object"==typeof e&&(i=new h(this,e)):i=new h(this,null),t.data(this,"plugin_"+n,i))})}}(jQuery,window,document);

},{}],5:[function(require,module,exports){
var Jed=require("jed");function Locale(e,n,r){var t=Jed.PF.compile(r),u=r.match(/nplurals\=(\d+);/),o=2;return u.length>1&&(o=u[1]),{getLocaleCode:function(){return e},getLanguageName:function(){return n},getInfo:function(){return e},getPluralCount:function(){return o},getNumbersForIndex:function(e){var n,r=[];for(n=0;n<1e3&&!(t(n)==e&&(r.push(n),r.length>=3));++n);return r}}}module.exports=Locale;

},{"jed":13}],6:[function(require,module,exports){
var Translation=require("./translation");function Original(n){var r,t=null,o=null,e=null;function l(n){var i={singular:r};return t&&(i.plural=t),n&&(i.context=n),i}return"string"==typeof n?r=n:"object"==typeof n&&"string"==typeof n.singular?(r=n.singular,t=n.plural):(r=n[0],t=n[1]),void 0!==t&&""!==t||(t=null),void 0!==n.originalId&&(e=n.originalId),void 0!==n.comment&&(o=n.comment),{type:"Original",getSingular:function(){return r},getPlural:function(){return t},generateJsonHash:function(n){return"string"==typeof n&&""!==n?n+""+r:r},getEmptyTranslation:function(n){var r=[""];if(null!==t)for(i=1;i<n.getPluralCount();i++)r.push("");return new Translation(n,r)},objectify:l,fetchIdAndTranslations:function(n,r){return n.queryByOriginal(l(r)).done(function(n){e=n.original_id,"string"==typeof n.original_comment&&(o=n.original_comment.replace(/^translators: /,""))})},getId:function(){return e},getComment:function(){return o}}}module.exports=Original;

},{"./translation":9}],7:[function(require,module,exports){
var locale;function Popover(t,n,a){var e,i;locale=n,e=t.isFullyTranslated()?getOverview(t):getInputForm(t),i="translator-original-"+t.getOriginal().getId();var r=function(){return e.attr("data-nodes",i),e.data("translationPair",t),e};return{attachTo:function(n){n.addClass(i).webuiPopover({title:"Translate to "+locale.getLanguageName()+'<a title="Help & Instructions" target="_blank" href="https://en.support.wordpress.com/in-page-translator/"><span class="noticon noticon-help"></span></a><a title="View in GlotPress" href="'+a.getPermalink(t)+'" target="_blank" class="gpPermalink"><span class="noticon noticon-external"></span></a>',content:jQuery("<div>").append(r()).html(),onload:popoverOnload,translationPair:t})},getTranslationHtml:function(){return e=getInputForm(t),r()}}}function popoverOnload(t){jQuery(t).find("textarea").eq(0).focus()}function getOriginalHtml(t){var n,a=t.getOriginal().getPlural();return n=a?'Singular: <strong class="singular"></strong><br/>Plural:  <strong class="plural"></strong>':'<strong class="singular"></strong>',(n=jQuery("<div>"+n)).find("strong.singular").text(t.getOriginal().getSingular()),a&&n.find("strong.plural").text(a),n}function getInputForm(t){var n,a=getHtmlTemplate("new-translation").clone(),e=a.find("div.original"),i=a.find("div.pair"),r=a.find("div.pairs");e.html(getOriginalHtml(t)),t.getContext()&&a.find("p.context").text(t.getContext()).show(),t.getOriginal().getComment()&&a.find("p.comment").text(t.getOriginal().getComment()).show(),n=t.getTranslation().getTextItems();for(var o=0;o<n.length;o++)o>0&&(i=i.eq(0).clone()),i.find("p").text(n[o].getCaption()),i.find("textarea").text(n[o].getText()).attr("placeholder","Could you help us and translate this to "+locale.getLanguageName()+"? Thanks!"),o>0&&r.append(i);return a}function getOverview(t){var n,a,e=getHtmlTemplate("existing-translation").clone(),i=e.find("div.original"),r=e.find("div.pair"),o=e.find("div.pairs");i.html(getOriginalHtml(t)),t.getContext()&&e.find("p.context").text(t.getContext()).show(),t.getOriginal().getComment()&&e.find("p.comment").text(t.getOriginal().getComment()).show(),n=t.getTranslation().getTextItems();for(var s=0;s<n.length;s++)s>0&&(r=r.eq(0).clone()),""!==(a=n[s].getInfoText())&&r.find("span.type").text(a+": "),r.find("span.translation").text(n[s].getText()),s>0&&o.append(r);return e}function getHtmlTemplate(t){switch(t){case"existing-translation":return jQuery('<form class="ct-existing-translation"><div class="original"></div><p class="context"></p><p class="comment"></p><hr /><p class="info"></p><div class="pairs"><div class="pair"><p dir="auto"><span class="type"></span><span class="translation"></span></p></div></div><button class="button button-primary">New Translation</button></form>');case"new-translation":return jQuery('<form class="ct-new-translation"><div class="original"></div><p class="context"></p><p class="comment"></p><p class="info"></p><div class="pairs"><div class="pair"><p></p><input type="hidden" class="original" name="original[]" /><textarea dir="auto" class="translation" name="translation[]"></textarea></div></div><button disabled class="button button-primary">Submit translation</button></form>')}}module.exports=Popover;

},{}],8:[function(require,module,exports){
var translationData,Original=require("./original"),Translation=require("./translation"),Popover=require("./popover");function TranslationPair(t,n,a,e){var r,i,o=[],s=!1;function l(n){if("object"==typeof n&&"Translation"===n.type||(n=new Translation(t,n.slice())),r.getTextItems().length!==n.getTextItems().length)return!1;o.push(n),r=n}function c(n){var a,e,r,i;for(o=[],a=0;a<n.length;a++){for(i=[],e=0;r=n[a]["translation_"+e];e++)i.push(r);l(i=new Translation(t,i.slice(),n[a]))}}function u(t){"number"==typeof t&&(t=t.toString()),o.length<=1||o.sort(function(t,n){return n.getComparableDate()-t.getComparableDate()});for(var n=0;n<o.length;n++){if(o[n].getUserId()===t&&o[n].getStatus())return void(r=o[n]);o[n].isCurrent()&&(r=o[n])}}return"object"==typeof n&&"Original"===n.type||(n=new Original(n)),"object"==typeof e?("Translation"!==e.type&&(e=new Translation(t,e)),o.push(e)):e=n.getEmptyTranslation(t),r=e,{type:"TranslationPair",createPopover:function(n,a){new Popover(this,t,a).attachTo(n)},isFullyTranslated:function(){return r.isFullyTranslated()},isTranslationWaiting:function(){return r.isWaiting()},getOriginal:function(){return n},getContext:function(){return a},getLocale:function(){return t},getScreenText:function(){return s},setScreenText:function(t){s=t},getTranslation:function(){return r},getGlotPressProject:function(){return i},updateAllTranslations:function(t,n){if(!c(t))return!1;void 0===n&&u(n)},serialize:function(){return{singular:n.getSingular(),plural:n.getPlural(),context:a,translations:r.serialize(),key:n.generateJsonHash(a)}},fetchOriginalAndTranslations:function(t,e){return n.fetchIdAndTranslations(t,a).done(function(t){var n;void 0!==t.translations&&(c(t.translations),u(e),void 0!==t.project&&(n=t.project,i=n))})}}}function extractFromDataElement(t){var n,a={singular:t.data("singular")};return t.data("plural")&&(a.plural=t.data("plural")),t.data("context")&&(a.context=t.data("context")),(n=new TranslationPair(translationData.locale,a,a.context)).setScreenText(t.text()),n}function trim(t){return void 0===t?"":t.replace(/(?:(?:^|\n)\s+|\s+(?:$|\n))/g,"")}function extractWithStringsUsedOnPage(t){var n;return"object"==typeof translationData.stringsUsedOnPage&&!t.is("style,script")&&!t.closest("#querylist").length&&(n=t.is("[data-i18n-context]")?t.data("i18n-context"):!!(n=t.closest("[data-i18n-context]")).length&&n.data("i18n-context"),translationPair=getTranslationPairForTextUsedOnPage(t,n),!1===translationPair&&void 0!==trim((t=t.clone(!0)).find("*").remove().end().text())&&(translationPair=getTranslationPairForTextUsedOnPage(t,n)),translationPair)}function anyChildMatches(t,n){var a,e;if("string"==typeof n&&(n=new RegExp(n)),n instanceof RegExp)for(e=t.children(),a=0;a<e.length;a++)if(n.test(e[a].innerHTML)||n.test(e[a].textContent))return!0;return!1}function getTranslationPairForTextUsedOnPage(t,n){var a,e,r,o=!1;if(!(e=trim(t.text())).length||e.length>3e3)return!1;if(void 0!==translationData.stringsUsedOnPage[e])return void 0!==(n=(o=translationData.stringsUsedOnPage[e])[1])&&n&&1===n.length&&(n=n[0]),(a=new TranslationPair(translationData.locale,o[0],n)).setScreenText(e),a;for(r=trim(t.html()),i=0;i<translationData.placeholdersUsedOnPage.length;i++)if((o=translationData.placeholdersUsedOnPage[i]).regex.test(r)){if(anyChildMatches(t,o.regex))continue;return(a=new TranslationPair(translationData.locale,o.original,o.context)).setScreenText(e),a}return!1}TranslationPair.extractFrom=function(t){return"object"==typeof translationData&&(t.is("data.translatable")?extractFromDataElement(t):t.closest("data.translatable").length?extractFromDataElement(t.closest("data.translatable")):extractWithStringsUsedOnPage(t))},TranslationPair.setTranslationData=function(t){var n,a,e=[];if("object"==typeof(translationData=t).placeholdersUsedOnPage)for(n in translationData.placeholdersUsedOnPage)void 0===(a=translationData.placeholdersUsedOnPage[n]).regex&&(a={original:a[0],regex:new RegExp("^\\s*"+a[1]+"\\s*$"),context:a[2]}),e.push(a);translationData.placeholdersUsedOnPage=e},TranslationPair._test={anyChildMatches:anyChildMatches},module.exports=TranslationPair;

},{"./original":6,"./popover":7,"./translation":9}],9:[function(require,module,exports){
function Translation(t,n,e){var r,u,i,a,o,s,l,f,d=0;if("object"==typeof e&&("undefined"!==e.status&&(i=e.status),"undefined"!==e.translation_id&&(a=e.translation_id),"undefined"!==e.user_id&&(o=e.user_id),"undefined"!==e.date_added&&(s=e.date_added)),"string"!=typeof i&&(i="current"),isNaN(a)&&(a=!1),isNaN(o)&&(o=!1),s&&(l=s.split("-"),f=l[2].substr(3).split(":"),d=new Date(l[0],l[1]-1,l[2].substr(0,2),f[0],f[1],f[2])),r=function(e,r){return{isTranslated:function(){return r.length>0},getCaption:function(){var r;return 1===n.length?"":2===n.length?0===e?"Singular":"Plural":(r=t.getNumbersForIndex(e)).length?"For numbers like: "+r.join(", "):""},getInfoText:function(){var r;return 1===n.length?"":2===n.length?0===e?"Singular":"Plural":(r=t.getNumbersForIndex(e)).length?r.join(", "):""},getText:function(){return r}}},"object"!=typeof n||"number"!=typeof n.length)return!1;for(u=0;u<n.length;u++)n[u]=new r(u,n[u]);return{type:"Translation",isFullyTranslated:function(){var t;for(t=0;t<n.length;t++)if(!1===n[t].isTranslated())return!1;return!0},isCurrent:function(){return"current"===i},isWaiting:function(){return"waiting"===i},getStatus:function(){return i},getDate:function(){return s},getComparableDate:function(){return d},getUserId:function(){return o},getTextItems:function(){return n},serialize:function(){var t,e=[];for(t=0;t<n.length;t++)e.push(n[t].getText());return e}}}module.exports=Translation;

},{}],10:[function(require,module,exports){
module.exports=function(e,t,n){return{walkTextNodes:function(o,r,a){var s,c;if("object"==typeof n)for(c=n.createTreeWalker(o,NodeFilter.SHOW_TEXT,null,!1);s=c.nextNode();)l(s);else t(o).find("*").contents().filter(function(){return 3===this.nodeType}).each(function(){l(this)});function l(n){var o,a=t(n.parentNode);return!a.is("script")&&!a.hasClass("translator-checked")&&(a.addClass("translator-checked"),!a.closest(".webui-popover").length&&(!1===(o=e.extractFrom(a))?(a.addClass("translator-dont-translate"),!1):("function"==typeof r&&r(o,a),!0)))}"function"==typeof a&&a()}}};

},{}],11:[function(require,module,exports){
(function (process){(function (){
/* eslint-env browser */

/**
 * This is the web browser implementation of `debug()`.
 */

exports.formatArgs = formatArgs;
exports.save = save;
exports.load = load;
exports.useColors = useColors;
exports.storage = localstorage();
exports.destroy = (() => {
	let warned = false;

	return () => {
		if (!warned) {
			warned = true;
			console.warn('Instance method `debug.destroy()` is deprecated and no longer does anything. It will be removed in the next major version of `debug`.');
		}
	};
})();

/**
 * Colors.
 */

exports.colors = [
	'#0000CC',
	'#0000FF',
	'#0033CC',
	'#0033FF',
	'#0066CC',
	'#0066FF',
	'#0099CC',
	'#0099FF',
	'#00CC00',
	'#00CC33',
	'#00CC66',
	'#00CC99',
	'#00CCCC',
	'#00CCFF',
	'#3300CC',
	'#3300FF',
	'#3333CC',
	'#3333FF',
	'#3366CC',
	'#3366FF',
	'#3399CC',
	'#3399FF',
	'#33CC00',
	'#33CC33',
	'#33CC66',
	'#33CC99',
	'#33CCCC',
	'#33CCFF',
	'#6600CC',
	'#6600FF',
	'#6633CC',
	'#6633FF',
	'#66CC00',
	'#66CC33',
	'#9900CC',
	'#9900FF',
	'#9933CC',
	'#9933FF',
	'#99CC00',
	'#99CC33',
	'#CC0000',
	'#CC0033',
	'#CC0066',
	'#CC0099',
	'#CC00CC',
	'#CC00FF',
	'#CC3300',
	'#CC3333',
	'#CC3366',
	'#CC3399',
	'#CC33CC',
	'#CC33FF',
	'#CC6600',
	'#CC6633',
	'#CC9900',
	'#CC9933',
	'#CCCC00',
	'#CCCC33',
	'#FF0000',
	'#FF0033',
	'#FF0066',
	'#FF0099',
	'#FF00CC',
	'#FF00FF',
	'#FF3300',
	'#FF3333',
	'#FF3366',
	'#FF3399',
	'#FF33CC',
	'#FF33FF',
	'#FF6600',
	'#FF6633',
	'#FF9900',
	'#FF9933',
	'#FFCC00',
	'#FFCC33'
];

/**
 * Currently only WebKit-based Web Inspectors, Firefox >= v31,
 * and the Firebug extension (any Firefox version) are known
 * to support "%c" CSS customizations.
 *
 * TODO: add a `localStorage` variable to explicitly enable/disable colors
 */

// eslint-disable-next-line complexity
function useColors() {
	// NB: In an Electron preload script, document will be defined but not fully
	// initialized. Since we know we're in Chrome, we'll just detect this case
	// explicitly
	if (typeof window !== 'undefined' && window.process && (window.process.type === 'renderer' || window.process.__nwjs)) {
		return true;
	}

	// Internet Explorer and Edge do not support colors.
	if (typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/(edge|trident)\/(\d+)/)) {
		return false;
	}

	// Is webkit? http://stackoverflow.com/a/16459606/376773
	// document is undefined in react-native: https://github.com/facebook/react-native/pull/1632
	return (typeof document !== 'undefined' && document.documentElement && document.documentElement.style && document.documentElement.style.WebkitAppearance) ||
		// Is firebug? http://stackoverflow.com/a/398120/376773
		(typeof window !== 'undefined' && window.console && (window.console.firebug || (window.console.exception && window.console.table))) ||
		// Is firefox >= v31?
		// https://developer.mozilla.org/en-US/docs/Tools/Web_Console#Styling_messages
		(typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/) && parseInt(RegExp.$1, 10) >= 31) ||
		// Double check webkit in userAgent just in case we are in a worker
		(typeof navigator !== 'undefined' && navigator.userAgent && navigator.userAgent.toLowerCase().match(/applewebkit\/(\d+)/));
}

/**
 * Colorize log arguments if enabled.
 *
 * @api public
 */

function formatArgs(args) {
	args[0] = (this.useColors ? '%c' : '') +
		this.namespace +
		(this.useColors ? ' %c' : ' ') +
		args[0] +
		(this.useColors ? '%c ' : ' ') +
		'+' + module.exports.humanize(this.diff);

	if (!this.useColors) {
		return;
	}

	const c = 'color: ' + this.color;
	args.splice(1, 0, c, 'color: inherit');

	// The final "%c" is somewhat tricky, because there could be other
	// arguments passed either before or after the %c, so we need to
	// figure out the correct index to insert the CSS into
	let index = 0;
	let lastC = 0;
	args[0].replace(/%[a-zA-Z%]/g, match => {
		if (match === '%%') {
			return;
		}
		index++;
		if (match === '%c') {
			// We only are interested in the *last* %c
			// (the user may have provided their own)
			lastC = index;
		}
	});

	args.splice(lastC, 0, c);
}

/**
 * Invokes `console.debug()` when available.
 * No-op when `console.debug` is not a "function".
 * If `console.debug` is not available, falls back
 * to `console.log`.
 *
 * @api public
 */
exports.log = console.debug || console.log || (() => {});

/**
 * Save `namespaces`.
 *
 * @param {String} namespaces
 * @api private
 */
function save(namespaces) {
	try {
		if (namespaces) {
			exports.storage.setItem('debug', namespaces);
		} else {
			exports.storage.removeItem('debug');
		}
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}
}

/**
 * Load `namespaces`.
 *
 * @return {String} returns the previously persisted debug modes
 * @api private
 */
function load() {
	let r;
	try {
		r = exports.storage.getItem('debug');
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}

	// If debug isn't set in LS, and we're in Electron, try to load $DEBUG
	if (!r && typeof process !== 'undefined' && 'env' in process) {
		r = process.env.DEBUG;
	}

	return r;
}

/**
 * Localstorage attempts to return the localstorage.
 *
 * This is necessary because safari throws
 * when a user disables cookies/localstorage
 * and you attempt to access it.
 *
 * @return {LocalStorage}
 * @api private
 */

function localstorage() {
	try {
		// TVMLKit (Apple TV JS Runtime) does not have a window object, just localStorage in the global context
		// The Browser also has localStorage in the global context.
		return localStorage;
	} catch (error) {
		// Swallow
		// XXX (@Qix-) should we be logging these?
	}
}

module.exports = require('./common')(exports);

const {formatters} = module.exports;

/**
 * Map %j to `JSON.stringify()`, since no Web Inspectors do that by default.
 */

formatters.j = function (v) {
	try {
		return JSON.stringify(v);
	} catch (error) {
		return '[UnexpectedJSONParseError]: ' + error.message;
	}
};

}).call(this)}).call(this,require('_process'))
},{"./common":12,"_process":15}],12:[function(require,module,exports){

/**
 * This is the common logic for both the Node.js and web browser
 * implementations of `debug()`.
 */

function setup(env) {
	createDebug.debug = createDebug;
	createDebug.default = createDebug;
	createDebug.coerce = coerce;
	createDebug.disable = disable;
	createDebug.enable = enable;
	createDebug.enabled = enabled;
	createDebug.humanize = require('ms');
	createDebug.destroy = destroy;

	Object.keys(env).forEach(key => {
		createDebug[key] = env[key];
	});

	/**
	* The currently active debug mode names, and names to skip.
	*/

	createDebug.names = [];
	createDebug.skips = [];

	/**
	* Map of special "%n" handling functions, for the debug "format" argument.
	*
	* Valid key names are a single, lower or upper-case letter, i.e. "n" and "N".
	*/
	createDebug.formatters = {};

	/**
	* Selects a color for a debug namespace
	* @param {String} namespace The namespace string for the debug instance to be colored
	* @return {Number|String} An ANSI color code for the given namespace
	* @api private
	*/
	function selectColor(namespace) {
		let hash = 0;

		for (let i = 0; i < namespace.length; i++) {
			hash = ((hash << 5) - hash) + namespace.charCodeAt(i);
			hash |= 0; // Convert to 32bit integer
		}

		return createDebug.colors[Math.abs(hash) % createDebug.colors.length];
	}
	createDebug.selectColor = selectColor;

	/**
	* Create a debugger with the given `namespace`.
	*
	* @param {String} namespace
	* @return {Function}
	* @api public
	*/
	function createDebug(namespace) {
		let prevTime;
		let enableOverride = null;
		let namespacesCache;
		let enabledCache;

		function debug(...args) {
			// Disabled?
			if (!debug.enabled) {
				return;
			}

			const self = debug;

			// Set `diff` timestamp
			const curr = Number(new Date());
			const ms = curr - (prevTime || curr);
			self.diff = ms;
			self.prev = prevTime;
			self.curr = curr;
			prevTime = curr;

			args[0] = createDebug.coerce(args[0]);

			if (typeof args[0] !== 'string') {
				// Anything else let's inspect with %O
				args.unshift('%O');
			}

			// Apply any `formatters` transformations
			let index = 0;
			args[0] = args[0].replace(/%([a-zA-Z%])/g, (match, format) => {
				// If we encounter an escaped % then don't increase the array index
				if (match === '%%') {
					return '%';
				}
				index++;
				const formatter = createDebug.formatters[format];
				if (typeof formatter === 'function') {
					const val = args[index];
					match = formatter.call(self, val);

					// Now we need to remove `args[index]` since it's inlined in the `format`
					args.splice(index, 1);
					index--;
				}
				return match;
			});

			// Apply env-specific formatting (colors, etc.)
			createDebug.formatArgs.call(self, args);

			const logFn = self.log || createDebug.log;
			logFn.apply(self, args);
		}

		debug.namespace = namespace;
		debug.useColors = createDebug.useColors();
		debug.color = createDebug.selectColor(namespace);
		debug.extend = extend;
		debug.destroy = createDebug.destroy; // XXX Temporary. Will be removed in the next major release.

		Object.defineProperty(debug, 'enabled', {
			enumerable: true,
			configurable: false,
			get: () => {
				if (enableOverride !== null) {
					return enableOverride;
				}
				if (namespacesCache !== createDebug.namespaces) {
					namespacesCache = createDebug.namespaces;
					enabledCache = createDebug.enabled(namespace);
				}

				return enabledCache;
			},
			set: v => {
				enableOverride = v;
			}
		});

		// Env-specific initialization logic for debug instances
		if (typeof createDebug.init === 'function') {
			createDebug.init(debug);
		}

		return debug;
	}

	function extend(namespace, delimiter) {
		const newDebug = createDebug(this.namespace + (typeof delimiter === 'undefined' ? ':' : delimiter) + namespace);
		newDebug.log = this.log;
		return newDebug;
	}

	/**
	* Enables a debug mode by namespaces. This can include modes
	* separated by a colon and wildcards.
	*
	* @param {String} namespaces
	* @api public
	*/
	function enable(namespaces) {
		createDebug.save(namespaces);
		createDebug.namespaces = namespaces;

		createDebug.names = [];
		createDebug.skips = [];

		let i;
		const split = (typeof namespaces === 'string' ? namespaces : '').split(/[\s,]+/);
		const len = split.length;

		for (i = 0; i < len; i++) {
			if (!split[i]) {
				// ignore empty strings
				continue;
			}

			namespaces = split[i].replace(/\*/g, '.*?');

			if (namespaces[0] === '-') {
				createDebug.skips.push(new RegExp('^' + namespaces.slice(1) + '$'));
			} else {
				createDebug.names.push(new RegExp('^' + namespaces + '$'));
			}
		}
	}

	/**
	* Disable debug output.
	*
	* @return {String} namespaces
	* @api public
	*/
	function disable() {
		const namespaces = [
			...createDebug.names.map(toNamespace),
			...createDebug.skips.map(toNamespace).map(namespace => '-' + namespace)
		].join(',');
		createDebug.enable('');
		return namespaces;
	}

	/**
	* Returns true if the given mode name is enabled, false otherwise.
	*
	* @param {String} name
	* @return {Boolean}
	* @api public
	*/
	function enabled(name) {
		if (name[name.length - 1] === '*') {
			return true;
		}

		let i;
		let len;

		for (i = 0, len = createDebug.skips.length; i < len; i++) {
			if (createDebug.skips[i].test(name)) {
				return false;
			}
		}

		for (i = 0, len = createDebug.names.length; i < len; i++) {
			if (createDebug.names[i].test(name)) {
				return true;
			}
		}

		return false;
	}

	/**
	* Convert regexp to namespace
	*
	* @param {RegExp} regxep
	* @return {String} namespace
	* @api private
	*/
	function toNamespace(regexp) {
		return regexp.toString()
			.substring(2, regexp.toString().length - 2)
			.replace(/\.\*\?$/, '*');
	}

	/**
	* Coerce `val`.
	*
	* @param {Mixed} val
	* @return {Mixed}
	* @api private
	*/
	function coerce(val) {
		if (val instanceof Error) {
			return val.stack || val.message;
		}
		return val;
	}

	/**
	* XXX DO NOT USE. This is a temporary stub function.
	* XXX It WILL be removed in the next major release.
	*/
	function destroy() {
		console.warn('Instance method `debug.destroy()` is deprecated and no longer does anything. It will be removed in the next major version of `debug`.');
	}

	createDebug.enable(createDebug.load());

	return createDebug;
}

module.exports = setup;

},{"ms":14}],13:[function(require,module,exports){
/**
 * @preserve jed.js https://github.com/SlexAxton/Jed
 */
/*
-----------
A gettext compatible i18n library for modern JavaScript Applications

by Alex Sexton - AlexSexton [at] gmail - @SlexAxton

MIT License

A jQuery Foundation project - requires CLA to contribute -
https://contribute.jquery.org/CLA/



Jed offers the entire applicable GNU gettext spec'd set of
functions, but also offers some nicer wrappers around them.
The api for gettext was written for a language with no function
overloading, so Jed allows a little more of that.

Many thanks to Joshua I. Miller - unrtst@cpan.org - who wrote
gettext.js back in 2008. I was able to vet a lot of my ideas
against his. I also made sure Jed passed against his tests
in order to offer easy upgrades -- jsgettext.berlios.de
*/
(function (root, undef) {

  // Set up some underscore-style functions, if you already have
  // underscore, feel free to delete this section, and use it
  // directly, however, the amount of functions used doesn't
  // warrant having underscore as a full dependency.
  // Underscore 1.3.0 was used to port and is licensed
  // under the MIT License by Jeremy Ashkenas.
  var ArrayProto    = Array.prototype,
      ObjProto      = Object.prototype,
      slice         = ArrayProto.slice,
      hasOwnProp    = ObjProto.hasOwnProperty,
      nativeForEach = ArrayProto.forEach,
      breaker       = {};

  // We're not using the OOP style _ so we don't need the
  // extra level of indirection. This still means that you
  // sub out for real `_` though.
  var _ = {
    forEach : function( obj, iterator, context ) {
      var i, l, key;
      if ( obj === null ) {
        return;
      }

      if ( nativeForEach && obj.forEach === nativeForEach ) {
        obj.forEach( iterator, context );
      }
      else if ( obj.length === +obj.length ) {
        for ( i = 0, l = obj.length; i < l; i++ ) {
          if ( i in obj && iterator.call( context, obj[i], i, obj ) === breaker ) {
            return;
          }
        }
      }
      else {
        for ( key in obj) {
          if ( hasOwnProp.call( obj, key ) ) {
            if ( iterator.call (context, obj[key], key, obj ) === breaker ) {
              return;
            }
          }
        }
      }
    },
    extend : function( obj ) {
      this.forEach( slice.call( arguments, 1 ), function ( source ) {
        for ( var prop in source ) {
          obj[prop] = source[prop];
        }
      });
      return obj;
    }
  };
  // END Miniature underscore impl

  // Jed is a constructor function
  var Jed = function ( options ) {
    // Some minimal defaults
    this.defaults = {
      "locale_data" : {
        "messages" : {
          "" : {
            "domain"       : "messages",
            "lang"         : "en",
            "plural_forms" : "nplurals=2; plural=(n != 1);"
          }
          // There are no default keys, though
        }
      },
      // The default domain if one is missing
      "domain" : "messages",
      // enable debug mode to log untranslated strings to the console
      "debug" : false
    };

    // Mix in the sent options with the default options
    this.options = _.extend( {}, this.defaults, options );
    this.textdomain( this.options.domain );

    if ( options.domain && ! this.options.locale_data[ this.options.domain ] ) {
      throw new Error('Text domain set to non-existent domain: `' + options.domain + '`');
    }
  };

  // The gettext spec sets this character as the default
  // delimiter for context lookups.
  // e.g.: context\u0004key
  // If your translation company uses something different,
  // just change this at any time and it will use that instead.
  Jed.context_delimiter = String.fromCharCode( 4 );

  function getPluralFormFunc ( plural_form_string ) {
    return Jed.PF.compile( plural_form_string || "nplurals=2; plural=(n != 1);");
  }

  function Chain( key, i18n ){
    this._key = key;
    this._i18n = i18n;
  }

  // Create a chainable api for adding args prettily
  _.extend( Chain.prototype, {
    onDomain : function ( domain ) {
      this._domain = domain;
      return this;
    },
    withContext : function ( context ) {
      this._context = context;
      return this;
    },
    ifPlural : function ( num, pkey ) {
      this._val = num;
      this._pkey = pkey;
      return this;
    },
    fetch : function ( sArr ) {
      if ( {}.toString.call( sArr ) != '[object Array]' ) {
        sArr = [].slice.call(arguments, 0);
      }
      return ( sArr && sArr.length ? Jed.sprintf : function(x){ return x; } )(
        this._i18n.dcnpgettext(this._domain, this._context, this._key, this._pkey, this._val),
        sArr
      );
    }
  });

  // Add functions to the Jed prototype.
  // These will be the functions on the object that's returned
  // from creating a `new Jed()`
  // These seem redundant, but they gzip pretty well.
  _.extend( Jed.prototype, {
    // The sexier api start point
    translate : function ( key ) {
      return new Chain( key, this );
    },

    textdomain : function ( domain ) {
      if ( ! domain ) {
        return this._textdomain;
      }
      this._textdomain = domain;
    },

    gettext : function ( key ) {
      return this.dcnpgettext.call( this, undef, undef, key );
    },

    dgettext : function ( domain, key ) {
     return this.dcnpgettext.call( this, domain, undef, key );
    },

    dcgettext : function ( domain , key /*, category */ ) {
      // Ignores the category anyways
      return this.dcnpgettext.call( this, domain, undef, key );
    },

    ngettext : function ( skey, pkey, val ) {
      return this.dcnpgettext.call( this, undef, undef, skey, pkey, val );
    },

    dngettext : function ( domain, skey, pkey, val ) {
      return this.dcnpgettext.call( this, domain, undef, skey, pkey, val );
    },

    dcngettext : function ( domain, skey, pkey, val/*, category */) {
      return this.dcnpgettext.call( this, domain, undef, skey, pkey, val );
    },

    pgettext : function ( context, key ) {
      return this.dcnpgettext.call( this, undef, context, key );
    },

    dpgettext : function ( domain, context, key ) {
      return this.dcnpgettext.call( this, domain, context, key );
    },

    dcpgettext : function ( domain, context, key/*, category */) {
      return this.dcnpgettext.call( this, domain, context, key );
    },

    npgettext : function ( context, skey, pkey, val ) {
      return this.dcnpgettext.call( this, undef, context, skey, pkey, val );
    },

    dnpgettext : function ( domain, context, skey, pkey, val ) {
      return this.dcnpgettext.call( this, domain, context, skey, pkey, val );
    },

    // The most fully qualified gettext function. It has every option.
    // Since it has every option, we can use it from every other method.
    // This is the bread and butter.
    // Technically there should be one more argument in this function for 'Category',
    // but since we never use it, we might as well not waste the bytes to define it.
    dcnpgettext : function ( domain, context, singular_key, plural_key, val ) {
      // Set some defaults

      plural_key = plural_key || singular_key;

      // Use the global domain default if one
      // isn't explicitly passed in
      domain = domain || this._textdomain;

      var fallback;

      // Handle special cases

      // No options found
      if ( ! this.options ) {
        // There's likely something wrong, but we'll return the correct key for english
        // We do this by instantiating a brand new Jed instance with the default set
        // for everything that could be broken.
        fallback = new Jed();
        return fallback.dcnpgettext.call( fallback, undefined, undefined, singular_key, plural_key, val );
      }

      // No translation data provided
      if ( ! this.options.locale_data ) {
        throw new Error('No locale data provided.');
      }

      if ( ! this.options.locale_data[ domain ] ) {
        throw new Error('Domain `' + domain + '` was not found.');
      }

      if ( ! this.options.locale_data[ domain ][ "" ] ) {
        throw new Error('No locale meta information provided.');
      }

      // Make sure we have a truthy key. Otherwise we might start looking
      // into the empty string key, which is the options for the locale
      // data.
      if ( ! singular_key ) {
        throw new Error('No translation key found.');
      }

      var key  = context ? context + Jed.context_delimiter + singular_key : singular_key,
          locale_data = this.options.locale_data,
          dict = locale_data[ domain ],
          defaultConf = (locale_data.messages || this.defaults.locale_data.messages)[""],
          pluralForms = dict[""].plural_forms || dict[""]["Plural-Forms"] || dict[""]["plural-forms"] || defaultConf.plural_forms || defaultConf["Plural-Forms"] || defaultConf["plural-forms"],
          val_list,
          res;

      var val_idx;
      if (val === undefined) {
        // No value passed in; assume singular key lookup.
        val_idx = 0;

      } else {
        // Value has been passed in; use plural-forms calculations.

        // Handle invalid numbers, but try casting strings for good measure
        if ( typeof val != 'number' ) {
          val = parseInt( val, 10 );

          if ( isNaN( val ) ) {
            throw new Error('The number that was passed in is not a number.');
          }
        }

        val_idx = getPluralFormFunc(pluralForms)(val);
      }

      // Throw an error if a domain isn't found
      if ( ! dict ) {
        throw new Error('No domain named `' + domain + '` could be found.');
      }

      val_list = dict[ key ];

      // If there is no match, then revert back to
      // english style singular/plural with the keys passed in.
      if ( ! val_list || val_idx > val_list.length ) {
        if (this.options.missing_key_callback) {
          this.options.missing_key_callback(key, domain);
        }
        res = [ singular_key, plural_key ];

        // collect untranslated strings
        if (this.options.debug===true) {
          console.log(res[ getPluralFormFunc(pluralForms)( val ) ]);
        }
        return res[ getPluralFormFunc()( val ) ];
      }

      res = val_list[ val_idx ];

      // This includes empty strings on purpose
      if ( ! res  ) {
        res = [ singular_key, plural_key ];
        return res[ getPluralFormFunc()( val ) ];
      }
      return res;
    }
  });


  // We add in sprintf capabilities for post translation value interolation
  // This is not internally used, so you can remove it if you have this
  // available somewhere else, or want to use a different system.

  // We _slightly_ modify the normal sprintf behavior to more gracefully handle
  // undefined values.

  /**
   sprintf() for JavaScript 0.7-beta1
   http://www.diveintojavascript.com/projects/javascript-sprintf

   Copyright (c) Alexandru Marasteanu <alexaholic [at) gmail (dot] com>
   All rights reserved.

   Redistribution and use in source and binary forms, with or without
   modification, are permitted provided that the following conditions are met:
       * Redistributions of source code must retain the above copyright
         notice, this list of conditions and the following disclaimer.
       * Redistributions in binary form must reproduce the above copyright
         notice, this list of conditions and the following disclaimer in the
         documentation and/or other materials provided with the distribution.
       * Neither the name of sprintf() for JavaScript nor the
         names of its contributors may be used to endorse or promote products
         derived from this software without specific prior written permission.

   THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
   ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
   WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
   DISCLAIMED. IN NO EVENT SHALL Alexandru Marasteanu BE LIABLE FOR ANY
   DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
   (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
   LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
   ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
   (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
   SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
  */
  var sprintf = (function() {
    function get_type(variable) {
      return Object.prototype.toString.call(variable).slice(8, -1).toLowerCase();
    }
    function str_repeat(input, multiplier) {
      for (var output = []; multiplier > 0; output[--multiplier] = input) {/* do nothing */}
      return output.join('');
    }

    var str_format = function() {
      if (!str_format.cache.hasOwnProperty(arguments[0])) {
        str_format.cache[arguments[0]] = str_format.parse(arguments[0]);
      }
      return str_format.format.call(null, str_format.cache[arguments[0]], arguments);
    };

    str_format.format = function(parse_tree, argv) {
      var cursor = 1, tree_length = parse_tree.length, node_type = '', arg, output = [], i, k, match, pad, pad_character, pad_length;
      for (i = 0; i < tree_length; i++) {
        node_type = get_type(parse_tree[i]);
        if (node_type === 'string') {
          output.push(parse_tree[i]);
        }
        else if (node_type === 'array') {
          match = parse_tree[i]; // convenience purposes only
          if (match[2]) { // keyword argument
            arg = argv[cursor];
            for (k = 0; k < match[2].length; k++) {
              if (!arg.hasOwnProperty(match[2][k])) {
                throw(sprintf('[sprintf] property "%s" does not exist', match[2][k]));
              }
              arg = arg[match[2][k]];
            }
          }
          else if (match[1]) { // positional argument (explicit)
            arg = argv[match[1]];
          }
          else { // positional argument (implicit)
            arg = argv[cursor++];
          }

          if (/[^s]/.test(match[8]) && (get_type(arg) != 'number')) {
            throw(sprintf('[sprintf] expecting number but found %s', get_type(arg)));
          }

          // Jed EDIT
          if ( typeof arg == 'undefined' || arg === null ) {
            arg = '';
          }
          // Jed EDIT

          switch (match[8]) {
            case 'b': arg = arg.toString(2); break;
            case 'c': arg = String.fromCharCode(arg); break;
            case 'd': arg = parseInt(arg, 10); break;
            case 'e': arg = match[7] ? arg.toExponential(match[7]) : arg.toExponential(); break;
            case 'f': arg = match[7] ? parseFloat(arg).toFixed(match[7]) : parseFloat(arg); break;
            case 'o': arg = arg.toString(8); break;
            case 's': arg = ((arg = String(arg)) && match[7] ? arg.substring(0, match[7]) : arg); break;
            case 'u': arg = Math.abs(arg); break;
            case 'x': arg = arg.toString(16); break;
            case 'X': arg = arg.toString(16).toUpperCase(); break;
          }
          arg = (/[def]/.test(match[8]) && match[3] && arg >= 0 ? '+'+ arg : arg);
          pad_character = match[4] ? match[4] == '0' ? '0' : match[4].charAt(1) : ' ';
          pad_length = match[6] - String(arg).length;
          pad = match[6] ? str_repeat(pad_character, pad_length) : '';
          output.push(match[5] ? arg + pad : pad + arg);
        }
      }
      return output.join('');
    };

    str_format.cache = {};

    str_format.parse = function(fmt) {
      var _fmt = fmt, match = [], parse_tree = [], arg_names = 0;
      while (_fmt) {
        if ((match = /^[^\x25]+/.exec(_fmt)) !== null) {
          parse_tree.push(match[0]);
        }
        else if ((match = /^\x25{2}/.exec(_fmt)) !== null) {
          parse_tree.push('%');
        }
        else if ((match = /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(_fmt)) !== null) {
          if (match[2]) {
            arg_names |= 1;
            var field_list = [], replacement_field = match[2], field_match = [];
            if ((field_match = /^([a-z_][a-z_\d]*)/i.exec(replacement_field)) !== null) {
              field_list.push(field_match[1]);
              while ((replacement_field = replacement_field.substring(field_match[0].length)) !== '') {
                if ((field_match = /^\.([a-z_][a-z_\d]*)/i.exec(replacement_field)) !== null) {
                  field_list.push(field_match[1]);
                }
                else if ((field_match = /^\[(\d+)\]/.exec(replacement_field)) !== null) {
                  field_list.push(field_match[1]);
                }
                else {
                  throw('[sprintf] huh?');
                }
              }
            }
            else {
              throw('[sprintf] huh?');
            }
            match[2] = field_list;
          }
          else {
            arg_names |= 2;
          }
          if (arg_names === 3) {
            throw('[sprintf] mixing positional and named placeholders is not (yet) supported');
          }
          parse_tree.push(match);
        }
        else {
          throw('[sprintf] huh?');
        }
        _fmt = _fmt.substring(match[0].length);
      }
      return parse_tree;
    };

    return str_format;
  })();

  var vsprintf = function(fmt, argv) {
    argv.unshift(fmt);
    return sprintf.apply(null, argv);
  };

  Jed.parse_plural = function ( plural_forms, n ) {
    plural_forms = plural_forms.replace(/n/g, n);
    return Jed.parse_expression(plural_forms);
  };

  Jed.sprintf = function ( fmt, args ) {
    if ( {}.toString.call( args ) == '[object Array]' ) {
      return vsprintf( fmt, [].slice.call(args) );
    }
    return sprintf.apply(this, [].slice.call(arguments) );
  };

  Jed.prototype.sprintf = function () {
    return Jed.sprintf.apply(this, arguments);
  };
  // END sprintf Implementation

  // Start the Plural forms section
  // This is a full plural form expression parser. It is used to avoid
  // running 'eval' or 'new Function' directly against the plural
  // forms.
  //
  // This can be important if you get translations done through a 3rd
  // party vendor. I encourage you to use this instead, however, I
  // also will provide a 'precompiler' that you can use at build time
  // to output valid/safe function representations of the plural form
  // expressions. This means you can build this code out for the most
  // part.
  Jed.PF = {};

  Jed.PF.parse = function ( p ) {
    var plural_str = Jed.PF.extractPluralExpr( p );
    return Jed.PF.parser.parse.call(Jed.PF.parser, plural_str);
  };

  Jed.PF.compile = function ( p ) {
    // Handle trues and falses as 0 and 1
    function imply( val ) {
      return (val === true ? 1 : val ? val : 0);
    }

    var ast = Jed.PF.parse( p );
    return function ( n ) {
      return imply( Jed.PF.interpreter( ast )( n ) );
    };
  };

  Jed.PF.interpreter = function ( ast ) {
    return function ( n ) {
      var res;
      switch ( ast.type ) {
        case 'GROUP':
          return Jed.PF.interpreter( ast.expr )( n );
        case 'TERNARY':
          if ( Jed.PF.interpreter( ast.expr )( n ) ) {
            return Jed.PF.interpreter( ast.truthy )( n );
          }
          return Jed.PF.interpreter( ast.falsey )( n );
        case 'OR':
          return Jed.PF.interpreter( ast.left )( n ) || Jed.PF.interpreter( ast.right )( n );
        case 'AND':
          return Jed.PF.interpreter( ast.left )( n ) && Jed.PF.interpreter( ast.right )( n );
        case 'LT':
          return Jed.PF.interpreter( ast.left )( n ) < Jed.PF.interpreter( ast.right )( n );
        case 'GT':
          return Jed.PF.interpreter( ast.left )( n ) > Jed.PF.interpreter( ast.right )( n );
        case 'LTE':
          return Jed.PF.interpreter( ast.left )( n ) <= Jed.PF.interpreter( ast.right )( n );
        case 'GTE':
          return Jed.PF.interpreter( ast.left )( n ) >= Jed.PF.interpreter( ast.right )( n );
        case 'EQ':
          return Jed.PF.interpreter( ast.left )( n ) == Jed.PF.interpreter( ast.right )( n );
        case 'NEQ':
          return Jed.PF.interpreter( ast.left )( n ) != Jed.PF.interpreter( ast.right )( n );
        case 'MOD':
          return Jed.PF.interpreter( ast.left )( n ) % Jed.PF.interpreter( ast.right )( n );
        case 'VAR':
          return n;
        case 'NUM':
          return ast.val;
        default:
          throw new Error("Invalid Token found.");
      }
    };
  };

  Jed.PF.extractPluralExpr = function ( p ) {
    // trim first
    p = p.replace(/^\s\s*/, '').replace(/\s\s*$/, '');

    if (! /;\s*$/.test(p)) {
      p = p.concat(';');
    }

    var nplurals_re = /nplurals\=(\d+);/,
        plural_re = /plural\=(.*);/,
        nplurals_matches = p.match( nplurals_re ),
        res = {},
        plural_matches;

    // Find the nplurals number
    if ( nplurals_matches.length > 1 ) {
      res.nplurals = nplurals_matches[1];
    }
    else {
      throw new Error('nplurals not found in plural_forms string: ' + p );
    }

    // remove that data to get to the formula
    p = p.replace( nplurals_re, "" );
    plural_matches = p.match( plural_re );

    if (!( plural_matches && plural_matches.length > 1 ) ) {
      throw new Error('`plural` expression not found: ' + p);
    }
    return plural_matches[ 1 ];
  };

  /* Jison generated parser */
  Jed.PF.parser = (function(){

var parser = {trace: function trace() { },
yy: {},
symbols_: {"error":2,"expressions":3,"e":4,"EOF":5,"?":6,":":7,"||":8,"&&":9,"<":10,"<=":11,">":12,">=":13,"!=":14,"==":15,"%":16,"(":17,")":18,"n":19,"NUMBER":20,"$accept":0,"$end":1},
terminals_: {2:"error",5:"EOF",6:"?",7:":",8:"||",9:"&&",10:"<",11:"<=",12:">",13:">=",14:"!=",15:"==",16:"%",17:"(",18:")",19:"n",20:"NUMBER"},
productions_: [0,[3,2],[4,5],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,3],[4,1],[4,1]],
performAction: function anonymous(yytext,yyleng,yylineno,yy,yystate,$$,_$) {

var $0 = $$.length - 1;
switch (yystate) {
case 1: return { type : 'GROUP', expr: $$[$0-1] };
break;
case 2:this.$ = { type: 'TERNARY', expr: $$[$0-4], truthy : $$[$0-2], falsey: $$[$0] };
break;
case 3:this.$ = { type: "OR", left: $$[$0-2], right: $$[$0] };
break;
case 4:this.$ = { type: "AND", left: $$[$0-2], right: $$[$0] };
break;
case 5:this.$ = { type: 'LT', left: $$[$0-2], right: $$[$0] };
break;
case 6:this.$ = { type: 'LTE', left: $$[$0-2], right: $$[$0] };
break;
case 7:this.$ = { type: 'GT', left: $$[$0-2], right: $$[$0] };
break;
case 8:this.$ = { type: 'GTE', left: $$[$0-2], right: $$[$0] };
break;
case 9:this.$ = { type: 'NEQ', left: $$[$0-2], right: $$[$0] };
break;
case 10:this.$ = { type: 'EQ', left: $$[$0-2], right: $$[$0] };
break;
case 11:this.$ = { type: 'MOD', left: $$[$0-2], right: $$[$0] };
break;
case 12:this.$ = { type: 'GROUP', expr: $$[$0-1] };
break;
case 13:this.$ = { type: 'VAR' };
break;
case 14:this.$ = { type: 'NUM', val: Number(yytext) };
break;
}
},
table: [{3:1,4:2,17:[1,3],19:[1,4],20:[1,5]},{1:[3]},{5:[1,6],6:[1,7],8:[1,8],9:[1,9],10:[1,10],11:[1,11],12:[1,12],13:[1,13],14:[1,14],15:[1,15],16:[1,16]},{4:17,17:[1,3],19:[1,4],20:[1,5]},{5:[2,13],6:[2,13],7:[2,13],8:[2,13],9:[2,13],10:[2,13],11:[2,13],12:[2,13],13:[2,13],14:[2,13],15:[2,13],16:[2,13],18:[2,13]},{5:[2,14],6:[2,14],7:[2,14],8:[2,14],9:[2,14],10:[2,14],11:[2,14],12:[2,14],13:[2,14],14:[2,14],15:[2,14],16:[2,14],18:[2,14]},{1:[2,1]},{4:18,17:[1,3],19:[1,4],20:[1,5]},{4:19,17:[1,3],19:[1,4],20:[1,5]},{4:20,17:[1,3],19:[1,4],20:[1,5]},{4:21,17:[1,3],19:[1,4],20:[1,5]},{4:22,17:[1,3],19:[1,4],20:[1,5]},{4:23,17:[1,3],19:[1,4],20:[1,5]},{4:24,17:[1,3],19:[1,4],20:[1,5]},{4:25,17:[1,3],19:[1,4],20:[1,5]},{4:26,17:[1,3],19:[1,4],20:[1,5]},{4:27,17:[1,3],19:[1,4],20:[1,5]},{6:[1,7],8:[1,8],9:[1,9],10:[1,10],11:[1,11],12:[1,12],13:[1,13],14:[1,14],15:[1,15],16:[1,16],18:[1,28]},{6:[1,7],7:[1,29],8:[1,8],9:[1,9],10:[1,10],11:[1,11],12:[1,12],13:[1,13],14:[1,14],15:[1,15],16:[1,16]},{5:[2,3],6:[2,3],7:[2,3],8:[2,3],9:[1,9],10:[1,10],11:[1,11],12:[1,12],13:[1,13],14:[1,14],15:[1,15],16:[1,16],18:[2,3]},{5:[2,4],6:[2,4],7:[2,4],8:[2,4],9:[2,4],10:[1,10],11:[1,11],12:[1,12],13:[1,13],14:[1,14],15:[1,15],16:[1,16],18:[2,4]},{5:[2,5],6:[2,5],7:[2,5],8:[2,5],9:[2,5],10:[2,5],11:[2,5],12:[2,5],13:[2,5],14:[2,5],15:[2,5],16:[1,16],18:[2,5]},{5:[2,6],6:[2,6],7:[2,6],8:[2,6],9:[2,6],10:[2,6],11:[2,6],12:[2,6],13:[2,6],14:[2,6],15:[2,6],16:[1,16],18:[2,6]},{5:[2,7],6:[2,7],7:[2,7],8:[2,7],9:[2,7],10:[2,7],11:[2,7],12:[2,7],13:[2,7],14:[2,7],15:[2,7],16:[1,16],18:[2,7]},{5:[2,8],6:[2,8],7:[2,8],8:[2,8],9:[2,8],10:[2,8],11:[2,8],12:[2,8],13:[2,8],14:[2,8],15:[2,8],16:[1,16],18:[2,8]},{5:[2,9],6:[2,9],7:[2,9],8:[2,9],9:[2,9],10:[2,9],11:[2,9],12:[2,9],13:[2,9],14:[2,9],15:[2,9],16:[1,16],18:[2,9]},{5:[2,10],6:[2,10],7:[2,10],8:[2,10],9:[2,10],10:[2,10],11:[2,10],12:[2,10],13:[2,10],14:[2,10],15:[2,10],16:[1,16],18:[2,10]},{5:[2,11],6:[2,11],7:[2,11],8:[2,11],9:[2,11],10:[2,11],11:[2,11],12:[2,11],13:[2,11],14:[2,11],15:[2,11],16:[2,11],18:[2,11]},{5:[2,12],6:[2,12],7:[2,12],8:[2,12],9:[2,12],10:[2,12],11:[2,12],12:[2,12],13:[2,12],14:[2,12],15:[2,12],16:[2,12],18:[2,12]},{4:30,17:[1,3],19:[1,4],20:[1,5]},{5:[2,2],6:[1,7],7:[2,2],8:[1,8],9:[1,9],10:[1,10],11:[1,11],12:[1,12],13:[1,13],14:[1,14],15:[1,15],16:[1,16],18:[2,2]}],
defaultActions: {6:[2,1]},
parseError: function parseError(str, hash) {
    throw new Error(str);
},
parse: function parse(input) {
    var self = this,
        stack = [0],
        vstack = [null], // semantic value stack
        lstack = [], // location stack
        table = this.table,
        yytext = '',
        yylineno = 0,
        yyleng = 0,
        recovering = 0,
        TERROR = 2,
        EOF = 1;

    //this.reductionCount = this.shiftCount = 0;

    this.lexer.setInput(input);
    this.lexer.yy = this.yy;
    this.yy.lexer = this.lexer;
    if (typeof this.lexer.yylloc == 'undefined')
        this.lexer.yylloc = {};
    var yyloc = this.lexer.yylloc;
    lstack.push(yyloc);

    if (typeof this.yy.parseError === 'function')
        this.parseError = this.yy.parseError;

    function popStack (n) {
        stack.length = stack.length - 2*n;
        vstack.length = vstack.length - n;
        lstack.length = lstack.length - n;
    }

    function lex() {
        var token;
        token = self.lexer.lex() || 1; // $end = 1
        // if token isn't its numeric value, convert
        if (typeof token !== 'number') {
            token = self.symbols_[token] || token;
        }
        return token;
    }

    var symbol, preErrorSymbol, state, action, a, r, yyval={},p,len,newState, expected;
    while (true) {
        // retreive state number from top of stack
        state = stack[stack.length-1];

        // use default actions if available
        if (this.defaultActions[state]) {
            action = this.defaultActions[state];
        } else {
            if (symbol == null)
                symbol = lex();
            // read action for current state and first input
            action = table[state] && table[state][symbol];
        }

        // handle parse error
        _handle_error:
        if (typeof action === 'undefined' || !action.length || !action[0]) {

            if (!recovering) {
                // Report error
                expected = [];
                for (p in table[state]) if (this.terminals_[p] && p > 2) {
                    expected.push("'"+this.terminals_[p]+"'");
                }
                var errStr = '';
                if (this.lexer.showPosition) {
                    errStr = 'Parse error on line '+(yylineno+1)+":\n"+this.lexer.showPosition()+"\nExpecting "+expected.join(', ') + ", got '" + this.terminals_[symbol]+ "'";
                } else {
                    errStr = 'Parse error on line '+(yylineno+1)+": Unexpected " +
                                  (symbol == 1 /*EOF*/ ? "end of input" :
                                              ("'"+(this.terminals_[symbol] || symbol)+"'"));
                }
                this.parseError(errStr,
                    {text: this.lexer.match, token: this.terminals_[symbol] || symbol, line: this.lexer.yylineno, loc: yyloc, expected: expected});
            }

            // just recovered from another error
            if (recovering == 3) {
                if (symbol == EOF) {
                    throw new Error(errStr || 'Parsing halted.');
                }

                // discard current lookahead and grab another
                yyleng = this.lexer.yyleng;
                yytext = this.lexer.yytext;
                yylineno = this.lexer.yylineno;
                yyloc = this.lexer.yylloc;
                symbol = lex();
            }

            // try to recover from error
            while (1) {
                // check for error recovery rule in this state
                if ((TERROR.toString()) in table[state]) {
                    break;
                }
                if (state == 0) {
                    throw new Error(errStr || 'Parsing halted.');
                }
                popStack(1);
                state = stack[stack.length-1];
            }

            preErrorSymbol = symbol; // save the lookahead token
            symbol = TERROR;         // insert generic error symbol as new lookahead
            state = stack[stack.length-1];
            action = table[state] && table[state][TERROR];
            recovering = 3; // allow 3 real symbols to be shifted before reporting a new error
        }

        // this shouldn't happen, unless resolve defaults are off
        if (action[0] instanceof Array && action.length > 1) {
            throw new Error('Parse Error: multiple actions possible at state: '+state+', token: '+symbol);
        }

        switch (action[0]) {

            case 1: // shift
                //this.shiftCount++;

                stack.push(symbol);
                vstack.push(this.lexer.yytext);
                lstack.push(this.lexer.yylloc);
                stack.push(action[1]); // push state
                symbol = null;
                if (!preErrorSymbol) { // normal execution/no error
                    yyleng = this.lexer.yyleng;
                    yytext = this.lexer.yytext;
                    yylineno = this.lexer.yylineno;
                    yyloc = this.lexer.yylloc;
                    if (recovering > 0)
                        recovering--;
                } else { // error just occurred, resume old lookahead f/ before error
                    symbol = preErrorSymbol;
                    preErrorSymbol = null;
                }
                break;

            case 2: // reduce
                //this.reductionCount++;

                len = this.productions_[action[1]][1];

                // perform semantic action
                yyval.$ = vstack[vstack.length-len]; // default to $$ = $1
                // default location, uses first token for firsts, last for lasts
                yyval._$ = {
                    first_line: lstack[lstack.length-(len||1)].first_line,
                    last_line: lstack[lstack.length-1].last_line,
                    first_column: lstack[lstack.length-(len||1)].first_column,
                    last_column: lstack[lstack.length-1].last_column
                };
                r = this.performAction.call(yyval, yytext, yyleng, yylineno, this.yy, action[1], vstack, lstack);

                if (typeof r !== 'undefined') {
                    return r;
                }

                // pop off stack
                if (len) {
                    stack = stack.slice(0,-1*len*2);
                    vstack = vstack.slice(0, -1*len);
                    lstack = lstack.slice(0, -1*len);
                }

                stack.push(this.productions_[action[1]][0]);    // push nonterminal (reduce)
                vstack.push(yyval.$);
                lstack.push(yyval._$);
                // goto new state = table[STATE][NONTERMINAL]
                newState = table[stack[stack.length-2]][stack[stack.length-1]];
                stack.push(newState);
                break;

            case 3: // accept
                return true;
        }

    }

    return true;
}};/* Jison generated lexer */
var lexer = (function(){

var lexer = ({EOF:1,
parseError:function parseError(str, hash) {
        if (this.yy.parseError) {
            this.yy.parseError(str, hash);
        } else {
            throw new Error(str);
        }
    },
setInput:function (input) {
        this._input = input;
        this._more = this._less = this.done = false;
        this.yylineno = this.yyleng = 0;
        this.yytext = this.matched = this.match = '';
        this.conditionStack = ['INITIAL'];
        this.yylloc = {first_line:1,first_column:0,last_line:1,last_column:0};
        return this;
    },
input:function () {
        var ch = this._input[0];
        this.yytext+=ch;
        this.yyleng++;
        this.match+=ch;
        this.matched+=ch;
        var lines = ch.match(/\n/);
        if (lines) this.yylineno++;
        this._input = this._input.slice(1);
        return ch;
    },
unput:function (ch) {
        this._input = ch + this._input;
        return this;
    },
more:function () {
        this._more = true;
        return this;
    },
pastInput:function () {
        var past = this.matched.substr(0, this.matched.length - this.match.length);
        return (past.length > 20 ? '...':'') + past.substr(-20).replace(/\n/g, "");
    },
upcomingInput:function () {
        var next = this.match;
        if (next.length < 20) {
            next += this._input.substr(0, 20-next.length);
        }
        return (next.substr(0,20)+(next.length > 20 ? '...':'')).replace(/\n/g, "");
    },
showPosition:function () {
        var pre = this.pastInput();
        var c = new Array(pre.length + 1).join("-");
        return pre + this.upcomingInput() + "\n" + c+"^";
    },
next:function () {
        if (this.done) {
            return this.EOF;
        }
        if (!this._input) this.done = true;

        var token,
            match,
            col,
            lines;
        if (!this._more) {
            this.yytext = '';
            this.match = '';
        }
        var rules = this._currentRules();
        for (var i=0;i < rules.length; i++) {
            match = this._input.match(this.rules[rules[i]]);
            if (match) {
                lines = match[0].match(/\n.*/g);
                if (lines) this.yylineno += lines.length;
                this.yylloc = {first_line: this.yylloc.last_line,
                               last_line: this.yylineno+1,
                               first_column: this.yylloc.last_column,
                               last_column: lines ? lines[lines.length-1].length-1 : this.yylloc.last_column + match[0].length}
                this.yytext += match[0];
                this.match += match[0];
                this.matches = match;
                this.yyleng = this.yytext.length;
                this._more = false;
                this._input = this._input.slice(match[0].length);
                this.matched += match[0];
                token = this.performAction.call(this, this.yy, this, rules[i],this.conditionStack[this.conditionStack.length-1]);
                if (token) return token;
                else return;
            }
        }
        if (this._input === "") {
            return this.EOF;
        } else {
            this.parseError('Lexical error on line '+(this.yylineno+1)+'. Unrecognized text.\n'+this.showPosition(),
                    {text: "", token: null, line: this.yylineno});
        }
    },
lex:function lex() {
        var r = this.next();
        if (typeof r !== 'undefined') {
            return r;
        } else {
            return this.lex();
        }
    },
begin:function begin(condition) {
        this.conditionStack.push(condition);
    },
popState:function popState() {
        return this.conditionStack.pop();
    },
_currentRules:function _currentRules() {
        return this.conditions[this.conditionStack[this.conditionStack.length-1]].rules;
    },
topState:function () {
        return this.conditionStack[this.conditionStack.length-2];
    },
pushState:function begin(condition) {
        this.begin(condition);
    }});
lexer.performAction = function anonymous(yy,yy_,$avoiding_name_collisions,YY_START) {

var YYSTATE=YY_START;
switch($avoiding_name_collisions) {
case 0:/* skip whitespace */
break;
case 1:return 20
break;
case 2:return 19
break;
case 3:return 8
break;
case 4:return 9
break;
case 5:return 6
break;
case 6:return 7
break;
case 7:return 11
break;
case 8:return 13
break;
case 9:return 10
break;
case 10:return 12
break;
case 11:return 14
break;
case 12:return 15
break;
case 13:return 16
break;
case 14:return 17
break;
case 15:return 18
break;
case 16:return 5
break;
case 17:return 'INVALID'
break;
}
};
lexer.rules = [/^\s+/,/^[0-9]+(\.[0-9]+)?\b/,/^n\b/,/^\|\|/,/^&&/,/^\?/,/^:/,/^<=/,/^>=/,/^</,/^>/,/^!=/,/^==/,/^%/,/^\(/,/^\)/,/^$/,/^./];
lexer.conditions = {"INITIAL":{"rules":[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17],"inclusive":true}};return lexer;})()
parser.lexer = lexer;
return parser;
})();
// End parser

  // Handle node, amd, and global systems
  if (typeof exports !== 'undefined') {
    if (typeof module !== 'undefined' && module.exports) {
      exports = module.exports = Jed;
    }
    exports.Jed = Jed;
  }
  else {
    if (typeof define === 'function' && define.amd) {
      define(function() {
        return Jed;
      });
    }
    // Leak a global regardless of module system
    root['Jed'] = Jed;
  }

})(this);

},{}],14:[function(require,module,exports){
/**
 * Helpers.
 */

var s = 1000;
var m = s * 60;
var h = m * 60;
var d = h * 24;
var w = d * 7;
var y = d * 365.25;

/**
 * Parse or format the given `val`.
 *
 * Options:
 *
 *  - `long` verbose formatting [false]
 *
 * @param {String|Number} val
 * @param {Object} [options]
 * @throws {Error} throw an error if val is not a non-empty string or a number
 * @return {String|Number}
 * @api public
 */

module.exports = function(val, options) {
  options = options || {};
  var type = typeof val;
  if (type === 'string' && val.length > 0) {
    return parse(val);
  } else if (type === 'number' && isFinite(val)) {
    return options.long ? fmtLong(val) : fmtShort(val);
  }
  throw new Error(
    'val is not a non-empty string or a valid number. val=' +
      JSON.stringify(val)
  );
};

/**
 * Parse the given `str` and return milliseconds.
 *
 * @param {String} str
 * @return {Number}
 * @api private
 */

function parse(str) {
  str = String(str);
  if (str.length > 100) {
    return;
  }
  var match = /^(-?(?:\d+)?\.?\d+) *(milliseconds?|msecs?|ms|seconds?|secs?|s|minutes?|mins?|m|hours?|hrs?|h|days?|d|weeks?|w|years?|yrs?|y)?$/i.exec(
    str
  );
  if (!match) {
    return;
  }
  var n = parseFloat(match[1]);
  var type = (match[2] || 'ms').toLowerCase();
  switch (type) {
    case 'years':
    case 'year':
    case 'yrs':
    case 'yr':
    case 'y':
      return n * y;
    case 'weeks':
    case 'week':
    case 'w':
      return n * w;
    case 'days':
    case 'day':
    case 'd':
      return n * d;
    case 'hours':
    case 'hour':
    case 'hrs':
    case 'hr':
    case 'h':
      return n * h;
    case 'minutes':
    case 'minute':
    case 'mins':
    case 'min':
    case 'm':
      return n * m;
    case 'seconds':
    case 'second':
    case 'secs':
    case 'sec':
    case 's':
      return n * s;
    case 'milliseconds':
    case 'millisecond':
    case 'msecs':
    case 'msec':
    case 'ms':
      return n;
    default:
      return undefined;
  }
}

/**
 * Short format for `ms`.
 *
 * @param {Number} ms
 * @return {String}
 * @api private
 */

function fmtShort(ms) {
  var msAbs = Math.abs(ms);
  if (msAbs >= d) {
    return Math.round(ms / d) + 'd';
  }
  if (msAbs >= h) {
    return Math.round(ms / h) + 'h';
  }
  if (msAbs >= m) {
    return Math.round(ms / m) + 'm';
  }
  if (msAbs >= s) {
    return Math.round(ms / s) + 's';
  }
  return ms + 'ms';
}

/**
 * Long format for `ms`.
 *
 * @param {Number} ms
 * @return {String}
 * @api private
 */

function fmtLong(ms) {
  var msAbs = Math.abs(ms);
  if (msAbs >= d) {
    return plural(ms, msAbs, d, 'day');
  }
  if (msAbs >= h) {
    return plural(ms, msAbs, h, 'hour');
  }
  if (msAbs >= m) {
    return plural(ms, msAbs, m, 'minute');
  }
  if (msAbs >= s) {
    return plural(ms, msAbs, s, 'second');
  }
  return ms + ' ms';
}

/**
 * Pluralization helper.
 */

function plural(ms, msAbs, n, name) {
  var isPlural = msAbs >= n * 1.5;
  return Math.round(ms / n) + ' ' + name + (isPlural ? 's' : '');
}

},{}],15:[function(require,module,exports){
// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };

},{}]},{},[3])(3)
});
