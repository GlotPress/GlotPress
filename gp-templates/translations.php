<?php
gp_title( sprintf( __( 'Translations &lt; %s &lt; %s &lt; GlotPress', 'glotpress' ), $translation_set->name, $project->name ) );
gp_breadcrumb( array(
	gp_project_links_from_root( $project ),
	gp_link_get( $url, $translation_set->name ),
) );
gp_enqueue_script( array( 'gp-editor', 'gp-translations-page' ) );
wp_localize_script( 'gp-translations-page', '$gp_translations_options', array( 'sort' => __( 'Sort', 'glotpress' ), 'filter' => __( 'Filter', 'glotpress' ) ) );

// localizer adds var in front of the variable name, so we can't use $gp.editor.options
$editor_options = compact('can_approve', 'can_write', 'url', 'discard_warning_url', 'set_priority_url', 'set_status_url');

wp_localize_script( 'gp-editor', '$gp_editor_options', $editor_options );

gp_tmpl_header();
$i = 0;
?>
<h2>
	<?php printf( __( 'Translation of %s', 'glotpress' ), esc_html( $project->name )); ?>: <?php echo esc_html( $translation_set->name ); ?>
	<?php gp_link_set_edit( $translation_set, $project, __( '(edit)', 'glotpress' ) ); ?>
	<?php gp_link_set_delete( $translation_set, $project, __( '(delete)', 'glotpress' ) ); ?>
	<?php if ( $glossary ): ?>
	<?php echo gp_link( $glossary->path(), __( 'glossary', 'glotpress' ), array('class'=>'glossary-link') ); ?>
	<?php elseif ( $can_approve ): ?>
		<?php echo gp_link_get( gp_url( '/glossaries/-new', array( 'translation_set_id' => $translation_set->id ) ), __( 'Create glossary', 'glotpress' ), array('class'=>'glossary-link') ); ?>
	<?php endif; ?>
</h2>
<?php if ( $can_approve ): ?>
<form id="bulk-actions-toolbar" class="filters-toolbar bulk-actions" action="<?php echo $bulk_action; ?>" method="post">
	<div>
	<select name="bulk[action]" id="bulk-action">
		<option value="" selected="selected"><?php _e( 'Bulk Actions', 'glotpress' ); ?></option>
		<option value="approve"><?php _e( 'Approve', 'glotpress' ); ?></option>
		<option value="reject"><?php _e( 'Reject', 'glotpress' ); ?></option>
	<?php if( $can_write ) : ?>
		<option value="set-priority"><?php _e( 'Set Priority', 'glotpress' ); ?></option>
	<?php endif; ?>
		<?php

		/**
		 * Fires inside the bulk action menu for translation sets.
		 *
		 * Printing out option elements here will add those to the translation
		 * bulk options drop down menu.
		 *
		 * @since 1.0.0
		 *
		 * @param GP_Translation_Set $set The translation set.
		 */
		do_action( 'gp_translation_set_bulk_action', $translation_set ); ?>
	</select>
	<?php if( $can_write ) : ?>
	<select name="bulk[priority]" id="bulk-priority">
	<?php
	foreach( GP::$original->get_static( 'priorities' ) as $value => $label ) {
		$selected = $value == 'normal' ? " selected='selected'" : '';
		echo "\t<option value='" . esc_attr( $value ) . "' $selected>" . esc_html( $label ) . "</option>\n";
	}
	?>
	</select>
	<?php endif; ?>
	<input type="hidden" name="bulk[redirect_to]" value="<?php echo esc_attr(gp_url_current()); ?>" id="bulk[redirect_to]" />
	<input type="hidden" name="bulk[row-ids]" value="" id="bulk[row-ids]" />
	<input type="submit" class="button" value="<?php esc_attr_e( 'Apply', 'glotpress' ); ?>" />
	</div>
	<?php gp_route_nonce_field( 'bulk-actions' ); ?>
</form>
<?php endif; ?>
<?php echo gp_pagination( $page, $per_page, $total_translations_count ); ?>
<form id="upper-filters-toolbar" class="filters-toolbar" action="" method="get" accept-charset="utf-8">
	<div>
	<a href="#" class="revealing filter"><?php _e( 'Filter &darr;', 'glotpress' ); ?></a> <span class="separator">&bull;</span>
	<a href="#" class="revealing sort"><?php _e( 'Sort &darr;', 'glotpress' ); ?></a> <strong class="separator">&bull;</strong>
	<?php
	$filter_links = array();
	$filter_links[] = gp_link_get( $url, __( 'All', 'glotpress' ) );
	$untranslated = gp_link_get( add_query_arg( array('filters[status]' => 'untranslated', 'sort[by]' => 'priority', 'sort[how]' => 'desc'), $url ), __( 'Untranslated', 'glotpress' ) );
	$untranslated .= '&nbsp;('.gp_link_get( add_query_arg( array('filters[status]' => 'untranslated', 'sort[by]' => 'random'), $url ), __( 'random', 'glotpress' ) ).')';
	$filter_links[] = $untranslated;
	if ( $can_approve ) {
		$filter_links[] = gp_link_get( add_query_arg( array('filters[translated]' => 'yes', 'filters[status]' => 'waiting'), $url ),
				__( 'Waiting', 'glotpress' ) );
		$filter_links[] = gp_link_get( add_query_arg( array('filters[translated]' => 'yes', 'filters[status]' => 'fuzzy'), $url ),
				__( 'Fuzzy', 'glotpress' ) );
		$filter_links[] = gp_link_get( add_query_arg( array('filters[warnings]' => 'yes', 'filters[status]' => 'current_or_waiting', 'sort[by]' => 'translation_date_added'), $url ),
				__( 'Warnings', 'glotpress' ) );

	}
	// TODO: with warnings
	// TODO: saved searches
	echo implode( '&nbsp;<span class="separator">&bull;</span>&nbsp;', $filter_links );
	?>
	</div>
	<dl class="filters-expanded filters hidden clearfix">
 		<dt>
			<p><label for="filters[term]"><?php _e( 'Term:', 'glotpress' ); ?></label></p>
			<p><label for="filters[user_login]"><?php _e( 'User:', 'glotpress' ); ?></label></p>
		</dt>
		<dd>
			<p><input type="text" value="<?php echo gp_esc_attr_with_entities( gp_array_get( $filters, 'term' ) ); ?>" name="filters[term]" id="filters[term]" /></p>
			<p><input type="text" value="<?php echo gp_esc_attr_with_entities( gp_array_get( $filters, 'user_login' ) ); ?>" name="filters[user_login]" id="filters[user_login]" /></p>
		</dd>
 		<dt><label><?php _e( 'Status:', 'glotpress' ); ?></label></dt>
		<dd>
			<?php echo gp_radio_buttons('filters[status]', //TODO: show only these, which user is allowed to see afterwards
				array(
					'current_or_waiting_or_fuzzy_or_untranslated' => __( 'Current/waiting/fuzzy + untranslated (All)', 'glotpress' ),
					'current' => __( 'Current only', 'glotpress' ),
					'old' => __( 'Approved, but obsoleted by another string', 'glotpress' ),
					'waiting' => __( 'Waiting approval', 'glotpress' ),
					'rejected' => __( 'Rejected', 'glotpress' ),
					'untranslated' => __( 'Without current translation', 'glotpress' ),
					'either' => __( 'Any', 'glotpress' ),
				), gp_array_get( $filters, 'status', 'current_or_waiting_or_fuzzy_or_untranslated' ) );
			?>
		</dd>
		<dd>
			<input type="checkbox" name="filters[with_comment]" value="yes" id="filters[with_comment][yes]" <?php gp_checked( 'yes' == gp_array_get( $filters, 'with_comment' ) ); ?>><label for='filters[with_comment][yes]'><?php _e( 'With comment', 'glotpress' ); ?></label><br />
			<input type="checkbox" name="filters[with_context]" value="yes" id="filters[with_context][yes]" <?php gp_checked( 'yes' == gp_array_get( $filters, 'with_context' ) ); ?>><label for='filters[with_context][yes]'><?php _e( 'With context', 'glotpress' ); ?></label><br />
			<input type="checkbox" name="filters[case_sensitive]" value="yes" id="filters[case_sensitive][yes]" <?php gp_checked( 'yes' == gp_array_get( $filters, 'case_sensitive' ) ); ?>><label for='filters[case_sensitive][yes]'><?php _e( 'Case sensitive', 'glotpress' ); ?></label>
		</dd>
		<?php

		/**
		 * Fires after the translation set filters options.
		 *
		 * This action is inside a DL element.
		 *
		 * @since 2.1.0
		 */
		do_action( 'gp_translation_set_filters_form' ); ?>

		<dd><input type="submit" value="<?php esc_attr_e( 'Filter', 'glotpress' ); ?>" name="filter" /></dd>
	</dl>
	<dl class="filters-expanded sort hidden clearfix">
		<dt><?php _x( 'By:', 'sort by', 'glotpress' ); ?></dt>
		<dd>
		<?php
		$default_sort = get_user_option( 'gp_default_sort' );
		if ( ! is_array( $default_sort ) ) {
			$default_sort = array(
				'by'  => 'priority',
				'how' => 'desc'
			);
		}

		$sort_bys = wp_list_pluck( gp_get_sort_by_fields(), 'title' );

		echo gp_radio_buttons( 'sort[by]', $sort_bys, gp_array_get( $sort, 'by', $default_sort['by'] ) );
		?>
		</dd>
		<dt><?php _e( 'Order:', 'glotpress' ); ?></dt>
		<dd>
		<?php echo gp_radio_buttons('sort[how]',
			array(
				'asc' => __( 'Ascending', 'glotpress' ),
				'desc' => __( 'Descending', 'glotpress' ),
			), gp_array_get( $sort, 'how', $default_sort['how'] ) );
		?>
		</dd>
		<?php

		/**
		 * Fires after the translation set sort options.
		 *
		 * This action is inside a DL element.
		 *
		 * @deprecated 2.1.0 Call gp_translation_set_sort_form instead
		 * @since 1.0.0
		 */
		do_action( 'gp_translation_set_filters' );

		/**
		 * Fires after the translation set sort options.
		 *
		 * This action is inside a DL element.
		 *
		 * @since 2.1.0
		 */
		do_action( 'gp_translation_set_sort_form' ); ?>

		<dd><input type="submit" value="<?php esc_attr_e( 'Sort', 'glotpress' ); ?>" name="sorts" /></dd>
	</dl>
</form>

<table id="translations" class="translations clear<?php if ( 'rtl' == $locale->text_direction ) { echo ' translation-sets-rtl'; } ?>">
	<thead>
	<tr>
		<?php if ( $can_approve ) : ?><th class="checkbox"><input type="checkbox" /></th><?php endif; ?>
		<th><?php /* Translators: Priority */ _e( 'Prio', 'glotpress' ); ?></th>
		<th class="original"><?php _e( 'Original string', 'glotpress' ); ?></th>
		<th class="translation"><?php _e( 'Translation', 'glotpress' ); ?></th>
		<th>&mdash;</th>
	</tr>
	</thead>
<?php
	if ( $glossary ) {
		$translations = map_glossary_entries_to_translations_originals( $translations, $glossary );
	}
?>
<?php foreach( $translations as $t ):
		gp_tmpl_load( 'translation-row', get_defined_vars() );
?>
<?php endforeach; ?>
<?php
	if ( !$translations ):
?>
	<tr><td colspan="<?php echo $can_approve ? 5 : 4; ?>"><?php _e( 'No translations were found!', 'glotpress' ); ?></td></tr>
<?php
	endif;
?>
</table>
<?php echo gp_pagination( $page, $per_page, $total_translations_count ); ?>
<div id="legend" class="secondary clearfix">
	<div><strong><?php _e( 'Legend:', 'glotpress' ); ?></strong></div>
<?php
	foreach( GP::$translation->get_static( 'statuses' ) as $status ):
		if ( 'rejected' == $status ) continue;
?>
	<div class="box status-<?php echo $status; ?>"></div>
	<div>
<?php
		switch( $status ) {
			case 'current':
				_e( 'Current', 'glotpress' );
				break;
			case 'waiting':
				_e( 'Waiting', 'glotpress' );
				break;
			case 'fuzzy':
				_e( 'Fuzzy', 'glotpress' );
				break;
			case 'old':
				_e( 'Old', 'glotpress' );
				break;
			default:
				echo $status;
		}
?>
	</div><?php endforeach; ?>
	<div class="box has-warnings"></div>
	<div><?php _e( 'with warnings', 'glotpress' ); ?></div>

</div>
<p class="clear actionlist secondary">
	<?php
		$footer_links = array();
		if ( ( isset( $can_import_current ) && $can_import_current ) || ( isset( $can_import_waiting ) && $can_import_waiting ) ) {
			$footer_links[] = gp_link_get( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'import-translations' ) ), __( 'Import translations', 'glotpress' ) );
		}
		$export_url = gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'export-translations' ) );
		$export_link = gp_link_get( $export_url , __( 'Export', 'glotpress' ), array('id' => 'export', 'filters' => add_query_arg( array( 'filters' => $filters ), $export_url ) ) );
		$format_options = array();
		foreach ( GP::$formats as $slug => $format ) {
			$format_options[$slug] = $format->name;
		}
		$what_dropdown = gp_select( 'what-to-export', array('all' => _x( 'all current', 'export choice', 'glotpress' ), 'filtered' => _x( 'only matching the filter', 'export choice', 'glotpress' ) ), 'all' );
		$format_dropdown = gp_select( 'export-format', $format_options, 'po' );
		/* translators: 1: export 2: what to export dropdown (all/filtered) 3: export format */
		$footer_links[] = sprintf( __( '%1$s %2$s as %3$s', 'glotpress' ), $export_link, $what_dropdown, $format_dropdown );

		/**
		 * Filter footer links in translations.
		 *
		 * @since 1.0.0
		 *
		 * @param array              $footer_links    Default links.
		 * @param GP_Project         $project         The current project.
		 * @param GP_Locale          $locale          The current locale.
		 * @param GP_Translation_Set $translation_set The current translation set.
		 */
		echo implode( ' &bull; ', apply_filters( 'gp_translations_footer_links', $footer_links, $project, $locale, $translation_set ) );
	?>
</p>
<?php gp_tmpl_footer();
