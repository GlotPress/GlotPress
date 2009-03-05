<?php
gp_title( sprintf( __( 'Translations &lt; %s &lt; GlotPress' ), $project->name ) );
gp_breadcrumb( array(
	gp_link_home_get(),
	gp_link_project_get( $project, $project->name ),
	$locale->combined_name(),
) );
gp_tmpl_header();
$parity = gp_parity_factory();
?>

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