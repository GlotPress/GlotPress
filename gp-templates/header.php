<?php
wp_enqueue_style( 'base' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<title><?php echo gp_title(); ?></title>
		<?php gp_head(); ?>
	</head>
	<body>
	    <div id="gp-js-message"></div>		
		<h1>
			<img alt="GlotPress logo" src="<?php echo gp_url_img( 'glotpress-logo.png' ); ?>" />
			<?php echo gp_breadcrumb(); ?>
			<span id="hello">
			<?php if (GP_User::logged_in()):
					$user = GP_User::current();
			?>
				Hi, <?php echo $user->user_login; ?>.
				<a href="<?php echo gp_url('/logout')?>">Log out</a>
			<?php else: ?>
				<a href="<?php echo gp_url('/login'); ?>">Log in</a>
			<?php endif; ?>
			</span>
		</h1>
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