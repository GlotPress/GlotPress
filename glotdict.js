jQuery(document).ready(function () {
  if(jQuery('.filters-toolbar div:first').length > 0) {
    gd_select_language();
    gd_add_terms();
    
  }
  
  function gd_select_language() {
    jQuery('.filters-toolbar div:first').append('<span class="separator">•</span><label for="gd-language-picker">Pick the glossary: </label><select id="gd-language-picker" class="glotdict_language"></select>');
    jQuery('.glossary-word').contents().unwrap();
    var lang = localStorage.getItem('gd_language');
    jQuery.each(['bg_BG', 'de_DE', 'es_ES', 'fi', 'fr_FR', 'hi_IN', 'it_IT', 'ja', 'lt_LT', 'nl_NL', 'sk_SK', 'sv_SE'], function(key, value) {  
       var new_option = jQuery('<option></option>').attr('value',value).text(value);
       if(lang === value) {
           new_option.attr('selected',true);
       }
       jQuery('.glotdict_language').append(new_option); 
    });
    if(jQuery('.glotdict_language option:selected').length === 0 ) {
        localStorage.setItem('gd_language', jQuery('.glotdict_language option:first-child').text());
    }
    jQuery('.glotdict_language').change(function() {
       localStorage.setItem('gd_language', jQuery('.glotdict_language option:selected').text());
       location.reload();
    });
  }
  
  function gd_add_terms() {
    jQuery.ajax({
      url: glotdict_path + '/' + get_lang() + '.json',
      dataType: 'text'
    }).done(function (data) {
      data = JSON.parse(data);
      jQuery('.editor .original').each(function () {
        var loop_editor = this;
        jQuery.each(data, function (i, item) {
            if(i !== '&') {
                gd_add_term(i, loop_editor, item.translation, item.pos, item.comment);
            }
        });
      });
      jQuery('.editor .original .glossary-word-glotdict').css({'cursor': 'help','border-bottom': '1px dashed'});
      jQuery('.editor').tooltip({
        items: '.editor .original .glossary-word-glotdict',
        content: function () {
          var content = jQuery('<ul>');
          jQuery.each(jQuery(this).data('translations'), function (i, e) {
            var def = jQuery('<li>');
            def.append(jQuery('<span>', {text: e.pos}).addClass('pos'));
            def.append(jQuery('<span>', {text: e.translation}).addClass('translation'));
            def.append(jQuery('<span>', {text: e.comment}).addClass('comment'));
            content.append(def);
          });
          return content;
        }
      });
    }).fail(function (xhr, ajaxOptions, thrownError) {
      console.error(thrownError);
      console.log('GlotDict: error on loading ' + gd_get_lang() + '.json');
    });
  }
  
  function gd_add_term(word, element, translation, pos, comment) {
    if(translation !== '') {
        var rgxp = new RegExp('(?!([^<]+)?>)\\b(' + word + ')\\b(?!([^>]+)?>)', 'gi');
        var repl = '<span class="glossary-word-glotdict" data-translations=\'[{"translation":"' + translation + '","pos":"' + pos + '","comment":"' + comment + '"}]\'>$2</span>';
        jQuery(element).html(jQuery(element).html().replace(rgxp, repl));
    }
  }
  
  function gd_get_lang() {
      var lang = localStorage.getItem('gd_language');
      if(lang === '' || lang === null) {
          lang = jQuery('.glotdict_language option:first-child').text();
      }
      return lang;
  }
});
