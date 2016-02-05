<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<title><?php echo gp_title(); ?></title>

	<?php gp_head(); ?>
</head>

<body <?php body_class( 'no-js' ); ?>>
	<script type="text/javascript">document.body.className = document.body.className.replace('no-js','js');</script>

	<header class="gp-bar clearfix">
		<h1>
			<a href="<?php echo gp_url( '/' ); ?>" rel="home">
				<?php echo apply_filters( 'gp_home_title', 'GlotPress' ); ?>
			</a>
		</h1>

		<nav id="main-navigation" role="navigation">
			<?php echo gp_nav_menu(); ?>
		</nav>

		<nav id="side-navigation">
			<?php echo gp_nav_menu('side'); ?>
		</nav>
	</header>

	<div class="gp-content">
		<?php echo gp_breadcrumb(); ?>

		<div id="gp-js-message"></div>

		<?php if (gp_notice('error')): ?>
			<div class="error">
				<?php echo gp_notice( 'error' ); ?>
			</div>
		<?php endif; ?>

		<?php if (gp_notice()): ?>
			<div class="notice">
				<?php echo gp_notice(); ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'gp_after_notices' ); ?>

