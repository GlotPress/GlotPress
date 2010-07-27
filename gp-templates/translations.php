<?php
gp_title( sprintf( __( 'Translations &lt; %s &lt; %s &lt; GlotPress' ), $translation_set->name, $project->name ) );
gp_breadcrumb( array(
	gp_link_project_get( $project, $project->name ),
	gp_link_get( $url, $locale->english_name . 'default' != $translation_set->slug? ' '.$translation_set->name : '' ),
) );
wp_enqueue_script( 'editor' );
wp_enqueue_script( 'translations-page' );
// localizer adds var in front of the variable name, so we can't use $gp.editor.options
$editor_options = compact('can_approve', 'can_write', 'url', 'discard_warning_url', 'set_priority_url', 'set_status_url');
$editor_options['google_translate_language'] = $locale->google_code;
wp_localize_script( 'editor', '$gp_editor_options', $editor_options );
wp_localize_script( 'translations-page', '$gp_translations_options', array('action' => $bulk_action) );
$parity = gp_parity_factory();
add_action( 'gp_head', lambda( '', 'gp_preferred_sans_serif_style_tag($locale);', compact( 'locale' ) ) );

gp_tmpl_header();
$i = 0;
?>
<!-- TODO: use another form for bulk actions -->
<form id="upper-filters-toolbar" class="filters-toolbar" action="" method="get" accept-charset="utf-8">
	<div>
	<?php if ( $can_approve ): ?>
	<a href="#" class="revealing bulk"><?php _e('Bulk &darr;'); ?></a> <strong class="separator">&bull;</strong>
	<?php endif; ?>
	<a href="#" class="revealing filter"><?php _e('Filter &darr;'); ?></a> <span class="separator">&bull;</span>
	<a href="#" class="revealing sort"><?php _e('Sort &darr;'); ?></a> <strong class="separator">&bull;</strong>
	<?php
	$filter_links = array();
	$filter_links[] = gp_link_get( $url, __('All') );
	$untranslated = gp_link_get( add_query_arg( array('filters[translated]' => 'no', 'sort[by]' => 'priority', 'sort[how]' => 'desc', 'filters[status]' => 'either'), $url ), __('Untranslated') );
	$untranslated .= '&nbsp;('.gp_link_get( add_query_arg( array('filters[translated]' => 'no', 'sort[by]' => 'random', 'filters[status]' => 'either'), $url ), __('random') ).')';
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
 		<dt><label><?php _e('With translation:'); ?></label></dt>
		<dd>
			<?php echo gp_radio_buttons('filters[translated]',
				array(
					'yes' => __('Yes'),
					'no'  => __('No'),
					'either' => __('Either'),
				), gp_array_get( $filters, 'translated', 'either' ) );
			?>
		</dd>
 		
 		<dt><label><?php _e('Status:'); ?></label></dt>
		<dd>
			<?php echo gp_radio_buttons('filters[status]', //TODO: show only these, which user is allowed to see afterwards
				array(
					'current_or_waiting_or_fuzzy' => __('Current/waiting/fuzzy'),
					'current' => __('Current only'),
					'old' => __('Approved, but obsoleted by another string'),
					'waiting' => __('Waiting approval'),
					'rejected' => __('Rejected'),
					'either' => __('Any'),
				), gp_array_get( $filters, 'status', 'current_or_waiting_or_fuzzy' ) );
			?>
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
	<dl class="hidden bulk-actions filters-expanded clearfix">
		<dt class="select"><?php _e('Select:'); ?></dt>
		<dd>
			<a href="#" class="all"><?php _e('All'); ?></a>
			<a href="#" class="none"><?php _e('None'); ?></a>
		</dd>
		<dd class="separator"></dd>
		<dd>
			<input type="hidden" name="bulk[redirect_to]" value="<?php echo esc_attr(gp_url_current()); ?>" id="bulk[redirect_to]" />
			<input type="hidden" name="bulk[row-ids]" value="" id="bulk[row-ids]" />
			<input type="submit" value="<?php echo esc_attr(__('Approve Selected')); ?>" name="approve" /><br />
			<input type="submit" value="<?php echo esc_attr(__('Reject Selected')); ?>" name="reject" />
		</dd>
		<dd class="separator"></dd>
		<dd>
			<input type="submit" value="<?php echo esc_attr(__('Translate via Google')); ?>" name="gtranslate" />
		</dd>
		<dd style="clear: both;">
			<p class="ternary"><?php _e('<strong>Note:</strong>&nbsp;Bulk edit works only on the current page.'); ?></p>
		</dd>
	</dl>
</form>

<?php echo gp_pagination( $page, $per_page, $total_translations_count ); ?>
<table id="translations" class="translations clear">
	<tr>
		<th><?php _e('&bull;'); ?></th>
		<th><?php _e('Prio'); ?></th>
		<th class="original"><?php _e('Original string'); ?></th>
		<th class="translation"><?php _e('Translation'); ?></th>
		<th>&mdash;</th>
	</tr>
<?php foreach( $translations as $t ):
		gp_tmpl_load( 'translation-row', get_defined_vars() );
?>
<?php endforeach; ?>
<?php
	if ( !$translations ):
?>
	<tr><td colspan="4"><?php _e('No translations were found!'); ?></td></tr>
<?php
	endif;
?>
</table>
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
<?php echo gp_pagination( $page, $per_page, $total_translations_count ); ?>
<p class="clear actionlist secondary">
	<?php
		$footer_links = array();
		if ( $can_approve ) {
			$footer_links[] = gp_link_get( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'import-translations' ) ), __('Import translations') );
		}
		if ( GP::$user->logged_in() ) {
			$export_url = gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'export-translations' ) );
			$export_link = gp_link_get( $export_url , __('Export'), array('id' => 'export', 'filters' => add_query_arg( array( 'filters' => $filters ), $export_url ) ) );
			$format_slugs = array_keys( GP::$formats );
			$what_dropdown = gp_select( 'what-to-export', array('all' => _x('all current', 'export choice'), 'filtered' => _x('only matching the filter', 'export choice')), 'all' );
			$format_dropdown = gp_select( 'export-format', array_combine( $format_slugs, $format_slugs ), 'po' );
			/* translators: 1: export 2: what to export dropdown (all/filtered) 3: export format */
			$footer_links[] = sprintf( __('%1$s %2$s as %3$s'), $export_link, $what_dropdown, $format_dropdown );
		}
		
		echo implode( ' &bull; ', apply_filters( 'translations_footer_links', $footer_links, $project, $locale, $translation_set ) );
	?>
</p>
<?php gp_tmpl_footer();
