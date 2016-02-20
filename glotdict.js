jQuery( document ).ready(function() {
    jQuery('table.translations')
	.on('click', 'a.edit', gd_add_terms)
	.on('dblclick', 'tr td', gd_add_terms);
    //gd_add_terms()
	
    function gd_add_terms(e) {
	e.preventDefault();
	var row_id = jQuery(this).closest('tr').attr('row');
	var editor = jQuery('#editor-' + row_id);
	if (!editor.length) {
		return;
	}
	jQuery.ajax({
            url: glotdict_path + "/it_IT.json",
            dataType: "json"
        }).done(function( data ) {
            jQuery.each(data, function(i, item) {
                console.log(item)
                add_term(i, '#editor-' + row_id + ' .original', item.translation, item.pos, item.comment);
                jQuery('#editor-' + row_id + ' .original .glossary-word-glotdict').css({'cursor': 'help','border-bottom':'1px dashed'});
                editor.tooltip({
                        items: '#editor-' + row_id + ' .original .glossary-word-glotdict',
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
            });
        }).fail(function() {
            console.log( "error on loading the manifest" );
        });
    }
    
    function add_term(word, element, translation, pos, comment) {
	element = document.querySelector(element);
	var rgxp = new RegExp(word, 'g');
	var repl = "<span class=\"glossary-word-glotdict\" data-translations='[{\"translation\":\"" + translation + "\",\"pos\":\"" + pos + "\",\"comment\":\"" + comment + "\"}]'>" + word + '</span>';
	element.innerHTML = element.innerHTML.replace(rgxp, repl);
    }
});