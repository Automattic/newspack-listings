/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';

/**
 * External dependencies
 */
import ListAlt from '@material-ui/icons/ListAlt';

/**
 * Internal dependencies
 */
import './editor.scss';
import { ListContainerEditor } from './edit';
import parentData from '../curated-list/block.json';

const parentAttributes = parentData.attributes;

export const registerListContainerBlock = () => {
	registerBlockType( 'newspack-listings/list-container', {
		title: __( 'Curated List', 'newspack-listing' ),
		icon: <ListAlt style={ { color: '#36f' } } />,
		category: 'newspack',
		parent: [ 'newspack-listings/curated-list' ],
		keywords: [
			__( 'curated', 'newspack-listings' ),
			__( 'list', 'newspack-listings' ),
			__( 'lists', 'newspack-listings' ),
			__( 'listings', 'newspack-listings' ),
			__( 'latest', 'newspack-listings' ),
		],

		attributes: parentAttributes,

		// Hide from block inserter menus.
		supports: {
			inserter: false,
		},

		edit: ListContainerEditor,
		save: () => <InnerBlocks.Content />, // also uses view.php
	} );
};
