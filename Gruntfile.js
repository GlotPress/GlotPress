module.exports = function( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	grunt.initConfig( {
		uglify: {
			options: {
				ASCIIOnly: true,
				screwIE8: false
			},
			core: {
				expand: true,
				cwd: '.',
				src: [
					'assets/js/*.js',
					'!assets/js/*.min.js',
					'assets/js/vendor/*.js',
					'!assets/js/vendor/*.min.js'
				],
				dest: [
					'assets/js/'
				],
				rename: function( dst, src ) {
					return src.replace( '.js', '.min.js' );
				}
			}
		},
		cssmin: {
			core: {
				expand: true,
				cwd: '.',
				src: [
					'assets/css/*.css',
					'!assets/css/*.min.css'
				],
				dest: [
					'assets/css/'
				],
				rename: function( dst, src ) {
					return src.replace( '.css', '.min.css' );
				}
			}
		}
	} );

	grunt.registerTask( 'default', [ 'uglify', 'cssmin' ] );
};
