<?php
gp_title( sprintf( __('Import Originals &lt; %s &lt; GlotPress'), gp_h( $project->name ) ) );
gp_breadcrumb( array(
	gp_link_home_get(),
	gp_link_project_get( $project, $project->name ),
	__('Import originals'),
) );
gp_tmpl_header();
?>
<?php if (gp_notice('error')): ?>
	<div class="error">
		<?php echo gp_notice('error'); ?>
	</div>
<?php endif; ?>
<?php if (gp_notice()): ?>
	<div class="notice">
		<?php echo gp_notice(); ?>
	</div>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">
	<input type="hidden" name="source" value="mo" id="source" />
	<p><label for="file"><?php echo __('MO file'); ?></label><input type="file" name="file" id="file" />
	</p>
	<p><input type="submit" value="<?php echo gp_attr( __('Import') ); ?>"></p>
</form>
<?php gp_tmpl_footer(); ?>