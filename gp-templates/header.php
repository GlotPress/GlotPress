<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<title><?php echo gp_title(); ?></title>

	<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>

	<?php
	gp_enqueue_styles( 'gp-app' );
	gp_enqueue_script( 'gp-app' );

	gp_head();
	?>
</head>

<body <?php body_class( 'no-js bg-gray-100' ); ?>>
<div class="flex flex-col h-screen">
	<header class="bg-gray-800 gp-header" role="banner">
		<div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
			<div class="relative flex items-center justify-between h-16">
				<div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
					<button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
						<span class="sr-only">Open main menu</span>
						<svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
						</svg>
						<svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
						</svg>
					</button>
				</div>
				<div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
					<div class="flex-shrink-0 flex items-center">
						<h1>
							<a href="<?php echo esc_url( gp_url( '/' ) ); ?>" rel="home" class="text-xl sm:text-2xl sm:-m-3 px-3 py-2 font-bold rounded-md text-white hover:bg-brand-purple">
								<?php
								/**
								 * Filter the main heading (H1) of a GlotPress page that links to the home page.
								 *
								 * @since 1.0.0
								 *
								 * @param string $title The text linking to home page.
								 */
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo apply_filters( 'gp_home_title', 'GlotPress' );
								?>
							</a>
						</h1>
					</div>
					<div id="main-navigation" class="hidden sm:block sm:ml-6">
						<nav class="flex space-x-4">
							<?php
							echo gp_nav_menu(
								'main',
								array(
									'class'        => 'text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium',
									'active_class' => 'bg-gray-900 text-white px-3 py-2 rounded-md text-sm font-medium',
								)
							);
							?>
						</nav>
					</div>
				</div>
				<div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
					<div id="profile-navigation-container" class="ml-3 relative">
						<div>
							<button type="button" class="bg-gray-800 flex text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" id="profile-navigation-button" aria-expanded="false" aria-haspopup="true">
								<span class="sr-only">Open profile menu</span>
								<?php
								echo get_avatar(
									get_current_user_id(),
									32,
									'',
									'',
									array(
										'class'         => 'h-8 w-8 rounded-full',
										'loading'       => false,
										'force_display' => true,
									)
								);
								?>
							</button>
						</div>

						<nav id="profile-navigation" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="profile-navigation-button">
							<?php
							echo gp_nav_menu(
								'side',
								array(
									'class'        => 'block px-4 py-2 text-sm text-gray-700',
									'active_class' => 'block px-4 py-2 text-sm text-gray-700 bg-gray-100',
									'role'         => 'menuitem',
								)
							);
							?>
						</nav>
					</div>
				</div>
			</div>
		</div>

		<div class="sm:hidden" id="mobile-main-navigation">
			<nav class="px-2 pt-2 pb-3 space-y-1">
				<?php
				echo gp_nav_menu(
					'main',
					array(
						'class'        => 'text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium',
						'active_class' => 'bg-gray-900 text-white block px-3 py-2 rounded-md text-base font-medium',
					)
				);
				?>
			</nav>
		</div>
	</header>

	<main class="flex-grow gp-content">
		<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
			<div class="bg-white shadow sm:rounded-lg">
				<div class="px-4 sm:px-6 py-3 gp-breadcrumb">
					<?php echo gp_breadcrumb(); ?>
				</div>
			</div>

			<div id="gp-js-message" class="gp-js-message"></div>

			<?php if ( gp_notice( 'error' ) ) : ?>
				<div class="error">
					<?php echo gp_notice( 'error' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( gp_notice() ) : ?>
				<div class="notice">
					<?php echo gp_notice(); ?>
				</div>
			<?php endif; ?>

			<?php
			/**
			 * Fires after the error and notice elements on the header.
			 *
			 * @since 1.0.0
			 */
			do_action( 'gp_after_notices' );
