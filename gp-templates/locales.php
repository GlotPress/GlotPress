<?php
gp_title( __( 'Locales &lt; GlotPress', 'glotpress' ) );

gp_enqueue_scripts( array( 'gp-common', 'tablesorter' ) );
gp_enqueue_style( 'tablesorter-theme' );
gp_breadcrumb( array( __( 'Locales', 'glotpress' ) ) );
gp_tmpl_header();
?>

	<h2><?php _e( 'Locales and Languages', 'glotpress' ); ?></h2>
	<div class="locales-filter">
		<?php _e( 'Filter:', 'glotpress' );?><input id="locales-filter" type="text" placeholder="<?php esc_attr_e('search', 'glotpress'); ?>" />
	</div>

	<table class="tablesorter locales tablesorter-glotpress">
		<thead>
		<tr class="tablesorter-headerRow">
			<th class="header tablesorter-header tablesorter-headerAsc"><?php _e( 'Name (in English)', 'glotpress' ); ?></th>
			<th class="header tablesorter-header tablesorter-headerUnSorted"><?php _e( 'Native name', 'glotpress' ); ?></th>
			<th class="header tablesorter-header tablesorter-headerUnSorted"><?php _e( 'Language code', 'glotpress' ); ?></th>
			<th class="header tablesorter-header tablesorter-headerUnSorted"><?php _e( 'GetText Plurals', 'glotpress' ); ?></th>
			<th class="header tablesorter-header tablesorter-headerUnSorted"><?php _e( 'CLDR Plurals', 'glotpress' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
			$class = '';

			foreach ( $locales as $locale ) :
				$class = ( 'odd' === $class ) ? 'even' : 'odd';
?>
			<tr class="<?php echo $class; // WPCS: XSS ok. ?>">
				<td><?php echo gp_link_get( gp_url_join( gp_url_current(), $locale->slug ), $locale->english_name ); ?></td>
				<td><?php echo gp_link_get( gp_url_join( gp_url_current(), $locale->slug ), $locale->native_name ); ?>
				<td><?php echo gp_link_get( gp_url_join( gp_url_current(), $locale->slug ), $locale->slug ); ?></td>
				<td class="locales-center"><?php ( $locale->nplurals > 0 ) ? _e( 'Yes', 'glotpress' ) : print ''; ?></td>
				<td class="locales-center"><?php ( $locale->cldr_nplurals > 0 ) ? _e( 'Yes', 'glotpress' ) : print ''; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function($) {
			$('.locales').tablesorter({
				theme: 'glotpress',
				sortList: [[0,0]],
				headers: {
					0: {
						sorter: 'text'
					}
				},
				widgets: ['zebra']
			});

			$rows = $('.locales tbody').find('tr');
			$('#locales-filter').bind("change keyup input",function() {
				var words = this.value.toLowerCase().split(' ');

				if ( '' == this.value.trim() ) {
					$rows.show();
				} else {
					$rows.hide();
					$rows.filter(function (i, v) {
						var $t = $(this);
						for ( var d = 0; d < words.length; ++d ) {
							if ( $t.text().toLowerCase().indexOf( words[d] )  != -1 ) {
								return true;
							}
						}
						return false;
					}).show();
				}
			});
		});
	</script>

<?php gp_tmpl_footer();
