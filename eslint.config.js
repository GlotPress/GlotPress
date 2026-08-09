const wordpress = require( '@wordpress/eslint-plugin' );

module.exports = [
	{
		ignores: [
			'**/*.min.js',
			'**/node_modules/**',
			'assets/js/jquery.webui-popover.js',
			'assets/js/driver-js.js',
			'**/vendor/**',
			'*.js',
		],
	},
	...wordpress.configs.es5,
	...wordpress.configs.i18n,
	{
		languageOptions: {
			globals: {
				jQuery: 'readonly',
			},
		},
		rules: {
			'computed-property-spacing': [ 'error', 'always' ],
		},
	},
];
