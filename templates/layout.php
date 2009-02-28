<?php
wp_enqueue_style( 'base' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<title><?php echo $title ?></title>
		<?php gp_head(); ?>
	</head>
	<body>
		<div id="header">
			<ul id="breadcrumb">
				<?php echo gp_tmpl_breadcrumb(); ?>
			</ul>
		</div>
	<?php gp_tmpl_load( $content_template, get_defined_vars() ); ?>
	<?php gp_footer(); ?>
	</body>
</html>