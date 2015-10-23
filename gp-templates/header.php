<?php 
add_filter( 'body_class', function( $classes ) {
	$classes[] = 'no-js';
    return $classes; 
}, 10, 2 );

add_action( 'get_header', 'gp_head' );

get_header();
?>
<div class="glotpress">
	<script type="text/javascript">document.body.className = document.body.className.replace('no-js','js');</script>

	<header class="gp-bar clearfix">
		<h1>
			<a href="<?php echo gp_url( '/' ); ?>" rel="home">
				<?php echo gp_get_option('title'); ?>
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
				<?php echo gp_notice( 'error' ); //TODO: run kses on notices ?>
			</div>
		<?php endif; ?>

		<?php if (gp_notice()): ?>
			<div class="notice">
				<?php echo gp_notice(); ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'gp_after_notices' ); ?>

