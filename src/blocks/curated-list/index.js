/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import { CuratedList } from './save';
import { CuratedListEditor } from './edit';
import metadata from './block.json';
const { attributes, category, name } = metadata;

export const registerCuratedListBlock = () => {
	registerBlockType( name, {
		title: __( 'Curated List', 'newspack-listing' ),
		icon: 'list-view',
		category,
		keywords: [
			__( 'curated', 'newspack-listings' ),
			__( 'list', 'newspack-listings' ),
			__( 'lists', 'newspack-listings' ),
			__( 'listings', 'newspack-listings' ),
			__( 'latest', 'newspack-listings' ),
		],

		attributes,

		edit: CuratedListEditor,
		save: CuratedList,
	} );
};
