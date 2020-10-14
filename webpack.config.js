/**
 **** WARNING: No ES6 modules here. Not transpiled! ****
 */
/* eslint-disable import/no-nodejs-modules */

/**
 * External dependencies
 */
const getBaseWebpackConfig = require( '@automattic/calypso-build/webpack.config.js' );
const path = require( 'path' );

/**
 * Internal variables
 */
const editor = path.join( __dirname, 'src', 'editor' );
const assets = [ path.join( __dirname, 'src', 'assets' ) ];
const amp = [ path.join( __dirname, 'src', 'assets', 'amp' ) ];

const webpackConfig = getBaseWebpackConfig(
	{ WP: true },
	{
		entry: { editor, assets, amp },
		'output-path': path.join( __dirname, 'dist' ),
	}
);

module.exports = webpackConfig;
