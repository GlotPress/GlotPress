module.exports = ({ env }) => ({
	plugins: {
		'postcss-import': {},
		tailwindcss: {},
		autoprefixer: {},
		cssnano: env === "production" ? {
			preset: [
				'default',
				{
					discardComments: { removeAll: true },
				},
			],
		} : false
	}
});
