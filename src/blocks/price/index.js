/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * External dependencies
 */
import Money from '@material-ui/icons/Money';

/**
 * Internal dependencies
 */
import './editor.scss';
import { PriceEditor } from './edit';
import metadata from './block.json';
const { attributes, category, name } = metadata;

export const registerPriceBlock = () => {
	registerBlockType( name, {
		title: __( 'Price', 'newspack-listing' ),
		icon: <Money style={ { color: '#36f' } } />,
		category,
		keywords: [
			__( 'curated', 'newspack-listings' ),
			__( 'list', 'newspack-listings' ),
			__( 'lists', 'newspack-listings' ),
			__( 'listings', 'newspack-listings' ),
			__( 'latest', 'newspack-listings' ),
			__( 'price', 'newspack-listings' ),
		],

		attributes,

		edit: PriceEditor,
		save: () => null, // uses view.php
	} );
};
