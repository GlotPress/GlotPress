jQuery( document ).ready(function() {
    jQuery('table.translations')
	.on('click', 'a.edit', gd_add_terms)
	.on('dblclick', 'tr td', gd_add_terms);
	
    function gd_add_terms(e) {
	e.preventDefault();
	var row_id = jQuery(this).closest('tr').attr('row');
	var editor = jQuery('#editor-' + row_id);
	if (!editor.length) {
		return;
	}
	//chrome.extension.getURL("images/myimage.png");
	add_term('saved', '#editor-' + row_id + ' .original');
        jQuery('.glossary-word-glotdict').css({'cursor': 'help','border-bottom':'1px dashed'});
	editor.tooltip({
		items: '.glossary-word-glotdict',
		content: function(){
			var content = jQuery('<ul>');
			jQuery.each( jQuery(this).data('translations'), function( i, e ){
				var def = jQuery('<li>');
				def.append( jQuery('<span>', {text: e.pos }).addClass('pos') );
				def.append( jQuery('<span>', {text: e.translation}).addClass('translation') );
				def.append( jQuery('<span>', {text: e.comment}).addClass('comment') );
				content.append(def);
			});
			return content;
		}
	});
    }
    
    function add_term(word, element) {
	element = document.querySelector(element);
	var rgxp = new RegExp(word, 'g');
	var repl = "<span class=\"glossary-word-glotdict\" data-translations='[{\"translation\":\"salvato\",\"pos\":\"noun\",\"comment\":\"\"}]'>" + word + '</span>';
	element.innerHTML = element.innerHTML.replace(rgxp, repl);
    }
});