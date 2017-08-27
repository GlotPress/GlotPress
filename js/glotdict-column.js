/* global glotdict_version, $gp, $gp_editor_options, DOMPurify */

function gd_add_column() {
  if (jQuery('#translations thead tr th').length < 6) {
	jQuery('#translations thead tr').append("<th></th>");
  }
  jQuery('#translations tr.preview').each(function ( ) {
	if (jQuery(this).find('td').length < 5) {
	  gd_add_column_buttons(this);
	}
  });
  jQuery('#translations tr.editor td').attr('colspan', 6);
}
jQuery('#translations').on('click', '.gd-approve', function () {
  var id = jQuery(this).parent().parent().attr('row');
  var nonce = jQuery('#editor-' + id + ' .meta button.approve').attr('data-nonce');
  gd_set_status(id, 'current', nonce);
});
jQuery('#translations').on('click', '.gd-reject', function () {
  var id = jQuery(this).parent().parent().attr('row');
  var nonce = jQuery('#editor-' + id + ' .meta button.reject').attr('data-nonce');
  gd_set_status(id, 'rejected', nonce);
});
jQuery('#translations').on('click', '.gd-fuzzy', function () {
  var id = jQuery(this).parent().parent().attr('row');
  var nonce = jQuery('#editor-' + id + ' .meta button.fuzzy').attr('data-nonce');
  gd_set_status(id, 'fuzzy', nonce);
});

function gd_add_column_buttons(element) {
  var approve = '';
  var reject = '';
  var fuzzy = '';
  var id = jQuery(element).attr('row');
  if (jQuery('#editor-' + id + ' .meta button.approve').length !== 0) {
	approve = '<button class="approve gd-approve"><strong>+</strong> Approve</button>';
  }
  if (jQuery('#editor-' + id + ' .meta button.reject').length !== 0) {
	reject = '<button class="reject gd-reject"><strong>−</strong> Reject</button>';
  }
  if (jQuery('#editor-' + id + ' .meta button.fuzzy').length !== 0) {
	fuzzy = '<button class="fuzzy gd-fuzzy"><strong>~</strong> Fuzzy</button>';
  }
  var buttons = approve + reject + fuzzy;

  if (jQuery(element).hasClass('untranslated')) {
	buttons = '';
  }
  jQuery(element).append("<td>" + buttons + "</td>");
}

function gd_set_status(id, status, nonce) {
  jQuery('#translations tr#preview-' + id + ' th.checkbox input').attr('checked', false);
  var string_id = id.split('-');
  $gp.notices.notice('Setting status to &#8220;' + status + '&#8221;&hellip;');
  data = {
	translation_id: string_id[1],
	status: status,
	_gp_route_nonce: nonce
  };

  jQuery.ajax({
	type: 'POST',
	url: $gp_editor_options.set_status_url,
	data: data,
	success: function (data) {
	  $gp.notices.success('Status set!');
	  // Sanitiziation for Firefox
	  data = DOMPurify.sanitize('<table>' + data + '</table>', {ADD_ATTR: ['row']});
	  data = data.replace("<table>\n<tbody>", '').replace("</tbody>\n</table>", '');
	  jQuery('#editor-' + id).after(data);
	  jQuery('.editor[row=' + id + ']').attr('id', 'editor-' + id);
	  jQuery('.preview[row=' + id + ']').attr('id', 'preview-' + id);
	  var old_current = jQuery('#editor-' + id);
	  old_current.attr('id', old_current.attr('id') + '-old').remove();
	  var old_current_preview = jQuery('#preview-' + id);
	  old_current_preview.attr('id', old_current_preview.attr('id') + '-old').remove();
	  gd_add_column_buttons(jQuery('#preview-' + id));
	},
	error: function (xhr, msg) {
	  msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error setting the status!';
	  $gp.notices.error(msg);
	}
  });
}

function gd_wait_table_alter() {
  if (document.querySelector('#translations tbody') !== null) {
	var observer = new MutationObserver(function (mutations) {
	  mutations.forEach(function () {
		gd_add_column();
	  });
	});

	observer.observe(document.querySelector('#translations tbody'), {attributes: true, childList: true, characterData: true});
  }
}
