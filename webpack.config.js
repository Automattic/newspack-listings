/**
 **** WARNING: No ES6 modules here. Not transpiled! ****
 */
/* eslint-disable import/no-nodejs-modules */

/**
 * External dependencies
 */
const fs = require( 'fs' );
const getBaseWebpackConfig = require( 'newspack-scripts/config/getWebpackConfig' );
const path = require( 'path' );

/**
 * Internal variables
 */
const editor = path.join( __dirname, 'src', 'editor' );
const assetsDir = path.join( __dirname, 'src', 'assets', 'front-end' );
const assets = fs
	.readdirSync( assetsDir )
	.filter( asset => /.js?$/.test( asset ) )
	.reduce(
		( acc, fileName ) => ( {
			...acc,
			[ fileName.replace( '.js', '' ) ]: path.join(
				__dirname,
				'src',
				'assets',
				'front-end',
				fileName
			),
		} ),
		{}
	);

const entry = {
	editor,
	...assets,
};

Object.keys( entry ).forEach( key => {
	entry[ key ] = [ 'regenerator-runtime/runtime', entry[ key ] ];
} );

const webpackConfig = getBaseWebpackConfig(
	{ WP: true },
	{
		entry,
		'output-path': path.join( __dirname, 'dist' ),
	}
);

module.exports = webpackConfig;
