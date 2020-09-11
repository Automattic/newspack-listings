/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import { CuratedListEditor } from './edit';
import metadata from './block.json';
const { attributes, category, name } = metadata;

export const registerListingBlock = () =>
	registerBlockType( name, {
		title: __( 'Listing' ),
		icon: 'list-view',
		category,
		keywords: [
			__( 'lists', 'newspack-listings' ),
			__( 'listings', 'newspack-listings' ),
			__( 'latest', 'newspack-listings' ),
		],

		attributes,

		edit: CuratedListEditor,
		save: () => null, // uses view.php
	} );
