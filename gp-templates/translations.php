<?php
gp_title( sprintf( __( 'Translations &lt; %s &lt; %s &lt; GlotPress' ), $translation_set->name, $project->name ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( $url, $locale->english_name . 'default' != $translation_set->slug? $translation_set->name : '' ),
) );
wp_enqueue_script( 'editor' );
wp_enqueue_script( 'translations-page' );
// localizer adds var in front of the variable name, so we can't use $gp.editor.options
$editor_options = compact('can_approve', 'can_write', 'url', 'discard_warning_url', 'set_priority_url', 'set_status_url');
$editor_options['google_translate_language'] = $locale->google_code;
wp_localize_script( 'editor', '$gp_editor_options', $editor_options );
$parity = gp_parity_factory();
add_action( 'gp_head', lambda( '', 'gp_preferred_sans_serif_style_tag($locale);', compact( 'locale' ) ) );

gp_tmpl_header();
$i = 0;
?>
<h2>
	Translation of <?php echo esc_html( $project->name ); ?>: <?php echo esc_html( $translation_set->name ); ?>
	<?php gp_link_set_edit( $translation_set, $project, '(edit)' ); ?>
</h2>
<?php if ( $can_approve ): ?>
<form id="bulk-actions-toolbar" class="filters-toolbar bulk-actions" action="<?php echo $bulk_action; ?>" method="post">
	<div>
	<select name="bulk[action]">
		<option value="" selected="selected">Bulk Actions</option>
		<option value="approve">Approve</option>
		<option value="reject">Reject</option>
		<option value="gtranslate">Translate via Google</option>
	</select>
	<input type="hidden" name="bulk[redirect_to]" value="<?php echo esc_attr(gp_url_current()); ?>" id="bulk[redirect_to]" />
	<input type="hidden" name="bulk[row-ids]" value="" id="bulk[row-ids]" />
	<input type="submit" class="button" value="<?php esc_attr_e( 'Apply' ); ?>" />
	</div>
</form>
<?php endif; ?>
<?php echo gp_pagination( $page, $per_page, $total_translations_count ); ?>
<form id="upper-filters-toolbar" class="filters-toolbar" action="" method="get" accept-charset="utf-8">
	<div>
	<a href="#" class="revealing filter"><?php _e('Search &darr;'); ?></a> <span class="separator">&bull;</span>
	<a href="#" class="revealing sort"><?php _e('Sort &darr;'); ?></a> <strong class="separator">&bull;</strong>
	<?php
	$filter_links = array();
	$filter_links[] = gp_link_get( $url, __('All') );
	$untranslated = gp_link_get( add_query_arg( array('filters[status]' => 'untranslated', 'sort[by]' => 'priority', 'sort[how]' => 'desc'), $url ), __('Untranslated') );
	$untranslated .= '&nbsp;('.gp_link_get( add_query_arg( array('filters[status]' => 'untranslated', 'sort[by]' => 'random'), $url ), __('random') ).')';
	$filter_links[] = $untranslated;
	if ( $can_approve ) {
		$filter_links[] = gp_link_get( add_query_arg( array('filters[translated]' => 'yes', 'filters[status]' => 'waiting'), $url ),
				__('Waiting') );
		$filter_links[] = gp_link_get( add_query_arg( array('filters[translated]' => 'yes', 'filters[status]' => 'fuzzy'), $url ),
				__('Fuzzy') );
		$filter_links[] = gp_link_get( add_query_arg( array('filters[warnings]' => 'yes', 'filters[status]' => 'current_or_waiting', 'sort[by]' => 'translation_date_added'), $url ),
				__('Warnings') );
	
	}
	// TODO: with warnings
	// TODO: saved searches
	echo implode( '&nbsp;<span class="separator">&bull;</span>&nbsp;', $filter_links );
	?>
	</div>
	<dl class="filters-expanded filters hidden clearfix">
 		<dt>
			<p><label for="filters[term]"><?php _e('Term:'); ?></label></p>
			<p><label for="filters[user_login]"><?php _e('User:'); ?></label></p>
		</dt>
		<dd>
			<p><input type="text" value="<?php echo gp_esc_attr_with_entities( gp_array_get( $filters, 'term' ) ); ?>" name="filters[term]" id="filters[term]" /></p>
			<p><input type="text" value="<?php echo gp_esc_attr_with_entities( gp_array_get( $filters, 'user_login' ) ); ?>" name="filters[user_login]" id="filters[user_login]" /></p>
		</dd>
 		<dt><label><?php _e('Status:'); ?></label></dt>
		<dd>
			<?php echo gp_radio_buttons('filters[status]', //TODO: show only these, which user is allowed to see afterwards
				array(
					'current_or_waiting_or_fuzzy_or_untranslated' => __('Current/waiting/fuzzy + untranslated (All)'),
					'current' => __('Current only'),
					'old' => __('Approved, but obsoleted by another string'),
					'waiting' => __('Waiting approval'),
					'rejected' => __('Rejected'),
					'untranslated' => __('Without current translation'),					
					'either' => __('Any'),
				), gp_array_get( $filters, 'status', 'current_or_waiting_or_fuzzy_or_untranslated' ) );
			?>
		</dd>
		<dd>
			<input type="checkbox" name="filters[with_comment]" value="yes" id="filters[with_comment][yes]" <?php gp_checked( 'yes' == gp_array_get( $filters, 'with_comment' ) ); ?>><label for='filters[with_comment][yes]'><?php _e( 'With comment' ); ?></label><br />
			<input type="checkbox" name="filters[with_context]" value="yes" id="filters[with_context][yes]" <?php gp_checked( 'yes' == gp_array_get( $filters, 'with_context' ) ); ?>><label for='filters[with_context][yes]'><?php _e( 'With context' ); ?></label>
		</dd>
		
		
		<dd><input type="submit" value="<?php echo esc_attr(__('Filter')); ?>" name="filter" /></dd>
	</dl>
	<dl class="filters-expanded sort hidden clearfix">
		<dt><?php _e('By:'); ?></dt>
		<dd>
		<?php echo gp_radio_buttons('sort[by]',
			array(
				'original_date_added' => __('Date added (original)'),
				'translation_date_added' => __('Date added (translation)'),
				'original' => __('Original string'),
				'translation' => __('Translation'),
				'priority' => __('Priority'),
				'references' => __('Filename in source'),
				'random' => __('Random'),
			), gp_array_get( $sort, 'by', 'priority' ) );
		?>
		</dd>
		<dt><?php _e('How:'); ?></dt>
		<dd>
		<?php echo gp_radio_buttons('sort[how]',
			array(
				'asc' => __('Ascending'),
				'desc' => __('Descending'),
			), gp_array_get( $sort, 'how', 'desc' ) );
		?>
		</dd>
		<dd><input type="submit" value="<?php echo esc_attr(__('Sort')); ?>" name="sorts" /></dd>
	</dl>
</form>
<table id="translations" class="translations clear">
	<thead>
	<tr>
		<?php if ( $can_approve ) : ?><th class="checkbox"><input type="checkbox" /></th><?php endif; ?>
		<th><?php /* Translators: Priority */ _e('Prio'); ?></th>
		<th class="original"><?php _e('Original string'); ?></th>
		<th class="translation"><?php _e('Translation'); ?></th>
		<th>&mdash;</th>
	</tr>
	</thead>
<?php foreach( $translations as $t ):
		gp_tmpl_load( 'translation-row', get_defined_vars() );
?>
<?php endforeach; ?>
<?php
	if ( !$translations ):
?>
	<tr><td colspan="<?php echo $can_approve ? 5 : 4; ?>"><?php _e('No translations were found!'); ?></td></tr>
<?php
	endif;
?>
</table>
<?php echo gp_pagination( $page, $per_page, $total_translations_count ); ?>
<div id="legend" class="secondary clearfix">
	<div><strong><?php _e('Legend:'); ?></strong></div>
<?php 
	foreach( GP::$translation->get_static( 'statuses' ) as $status ):
		if ( 'rejected' == $status ) continue;
?>
	<div class="box status-<?php echo $status; ?>"></div>
	<div><?php echo $status; ?></div>
<?php endforeach; ?>
	<div class="box has-warnings"></div>
	<div><?php _e('with warnings'); ?></div>

</div>
<p class="clear actionlist secondary">
	<?php
		$footer_links = array();
		if ( $can_approve ) {
			$footer_links[] = gp_link_get( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'import-translations' ) ), __('Import translations') );
		}
		$export_url = gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'export-translations' ) );
		$export_link = gp_link_get( $export_url , __('Export'), array('id' => 'export', 'filters' => add_query_arg( array( 'filters' => $filters ), $export_url ) ) );
		$format_slugs = array_keys( GP::$formats );
		$what_dropdown = gp_select( 'what-to-export', array('all' => _x('all current', 'export choice'), 'filtered' => _x('only matching the filter', 'export choice')), 'all' );
		$format_dropdown = gp_select( 'export-format', array_combine( $format_slugs, $format_slugs ), 'po' );
		/* translators: 1: export 2: what to export dropdown (all/filtered) 3: export format */
		$footer_links[] = sprintf( __('%1$s %2$s as %3$s'), $export_link, $what_dropdown, $format_dropdown );
		
		echo implode( ' &bull; ', apply_filters( 'translations_footer_links', $footer_links, $project, $locale, $translation_set ) );
	?>
</p>
<?php gp_tmpl_footer();
