/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ListContainerEditor } from './edit';

export const registerListContainerBlock = () => {
	registerBlockType( 'newspack-listings/list-container', {
		title: __( 'Curated List Container', 'newspack-listing' ),
		icon: 'list-view',
		category: 'newspack',
		keywords: [
			__( 'curated', 'newspack-listings' ),
			__( 'list', 'newspack-listings' ),
			__( 'lists', 'newspack-listings' ),
			__( 'listings', 'newspack-listings' ),
			__( 'latest', 'newspack-listings' ),
		],

		attributes: {},

		// Hide from block inserter menus.
		supports: {
			inserter: false,
		},

		edit: ListContainerEditor,
		save: () => <InnerBlocks.Content />, // also uses view.php
	} );
};
