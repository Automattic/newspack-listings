/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, group } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './editor.scss';
import { ListContainerEditor } from './edit';

export const registerListContainerBlock = () => {
	registerBlockType( 'newspack-listings/list-container', {
		title: __( 'Curated List Container', 'newspack-listing' ),
		icon: {
			src: <Icon icon={ group } />,
			foreground: '#36f',
		},
		category: 'newspack',
		parent: [ 'newspack-listings/curated-list' ],
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
