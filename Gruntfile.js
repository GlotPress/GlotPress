/* jshint node:true */
module.exports = function( grunt ) {
	var WORKING_DIR = '.';

	require( 'load-grunt-tasks' )( grunt );

	grunt.initConfig( {
		uglify: {
			options: {
				output: {
					ascii_only: true
				}
			},
			core: {
				expand: true,
				cwd: WORKING_DIR,
				dest: WORKING_DIR,
				src: [
					'assets/js/**/*.js',

					// Exceptions.
					'!assets/js/vendor/*',
					'!**/*.min.js'
				],

				// Custom rename function to support files with multiple dots.
				rename: function( dst, src ) {
					return src.replace( '.js', '.min.js' );
				}
			}
		},
		cssmin: {
			core: {
				expand: true,
				cwd: WORKING_DIR,
				dest: WORKING_DIR,
				src: [
					'assets/css/*.css',

					// Exceptions.
					'!**/*.min.css'
				],

				// Custom rename function to support files with multiple dots.
				rename: function( dst, src ) {
					return src.replace( '.css', '.min.css' );
				}
			}
		},
		watch: {
			js: {
				files: [
					'assets/js/**/*.js',

					// Exceptions.
					'!**/*.min.js'
				],
				tasks: [ 'uglify' ],
			},
			css: {
				files: [
					'assets/css/*.css',

					// Exceptions.
					'!**/*.min.css'
				],
				tasks: [ 'cssmin' ],
			},
		},
		replace: {
			prepare-release: {
				src: ['glotpress.php'],
				overwrite: true,
				replacements: [
					{
						from: /define\( 'GP_SCRIPT_DEBUG', true \);/g,
						to: "define( 'GP_SCRIPT_DEBUG', false );" // Replacement text
					}
				]
			}
		}
	} );

	grunt.registerTask( 'default', [ 'uglify', 'cssmin' ] );
};
