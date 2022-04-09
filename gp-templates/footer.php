	</div>
	<footer id="gp-footer" class="gp-footer">
		<div class="gp-footer-credits">
			<p class="gp-footer-copyright">&copy;
				<?php
				echo date_i18n(
					/* translators: Copyright date format, see https://www.php.net/manual/datetime.format.php */
					_x( 'Y', 'copyright date format', 'glotpress' )
				);
				?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			</p>

			<?php
			if ( function_exists( 'the_privacy_policy_link' ) ) {
				the_privacy_policy_link( '<p class="gp-privacy-policy">', '</p>' );
			}
			?>

			<p class="gp-powered-by">
				<?php
				printf(
					/* translators: %s: GlotPress link. */
					__( 'Proudly powered by %s', 'glotpress' ),
					'<a rel="nofollow" href="https://glotpress.blog/">GlotPress</a>'
				);
				?>
			</p>

		</div>

		<?php gp_footer(); ?>
	</footer>
	</body>
</html>
