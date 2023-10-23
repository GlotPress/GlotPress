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
	<div class="tour-dashboard">
		<div class="tour-dashboard-btn">
			<a href="#TB_inline?&width=600&height=550&inlineId=tour-form-content" class="thickbox create-tour-btn">Create Tour</a>	
		</div>
	</div>
	<div class="tour-settings-btn">Tour Settings</div>
	<div id="tour-form-content" style="display:none;">
		<form>
			<div>
				<label>Tour Title</label><br>
				<input type="text" class="tour-form-field" id="tour-title" />
			</div>
			<div>
				<label>Tour Description</label><br>
				<textarea class="tour-form-field tour-form-textarea" id="tour-desc"></textarea>
			</div>
			<div>
				<button class="tour-form-field tour-form-submit" type="submit">Create Tour</button>
			</div>
	 	</form>
	</div>
	</body>
</html>
