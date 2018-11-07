
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-markdownlint' );

grunt.initConfig( {
		jsonlint: {
			all: [
				'**/*.json'
			]
},
		markdownlint: {
			options: {
				config: {
					'default': true,
					MD013: false
				}
			},
			all: [
				'**/*.md'
			]
		}
} );
	grunt.registerTask( 'lint',
		[
			'jsonlint',
			'markdownlint'
		] );
	grunt.registerTask( 'test',
		[
			'lint',
			'smell'
		] );
	grunt.registerTask( 'default',
		[
			'test'
] );
};
