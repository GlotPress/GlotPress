module.exports = {
	purge: [
		'./gp-templates/*.php',
		'./gp-includes/template.php',

	],
	darkMode: 'media', // or 'media' or 'class'
	theme: {
		extend: {
			colors: {
				'brand-purple': '#826EB4',
			},
		},
	},
	variants: {
		extend: {},
	},
	plugins: [],
}
