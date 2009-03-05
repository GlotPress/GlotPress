<?php
gp_title( sprintf( __( 'Translations &lt; %s &lt; GlotPress' ), $project->name ) );
gp_tmpl_header();
$parity = gp_parity_factory();
?>
<h1><?php printf( __('Translations for %s in %s'), $project->name, $locale->combined_name() ); ?></h1>

<table class="translations">
	<tr>
		<th class="original"><?php _e('Original string'); ?></th>
		<th class="translation"><?php _e('Translation'); ?></th>
		<th><?php _e('Actions'); ?></th>
	</tr>
<?php foreach( $translations as $t ): ?>
	<tr class="<?php echo $parity(); ?>">
		<td class="original"><?php echo gp_h( $t->singular ); ?></td>
		<td class="translation"><?php echo gp_h( $t->translation_0 ); ?></td>
		<td class="actions">
		</td>
	</tr>
<?php endforeach; ?>
</table>
<?php gp_tmpl_footer(); ?>