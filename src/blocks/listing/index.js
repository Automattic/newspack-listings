/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import { ListingEditor } from './edit';
import metadata from './block.json';
const { attributes, category } = metadata;
const { post_types } = window.newspack_listings_data;

export const registerListingBlock = () => {
	for ( const listingType in post_types ) {
		if ( post_types.hasOwnProperty( listingType ) && 'curated_list' !== listingType ) {
			registerBlockType( `newspack-listings/${ listingType }`, {
				title: listingType.charAt( 0 ).toUpperCase() + listingType.slice( 1 ),
				icon: 'list-view',
				category,
				keywords: [
					__( 'lists', 'newspack-listings' ),
					__( 'listings', 'newspack-listings' ),
					__( 'latest', 'newspack-listings' ),
				],

				attributes,

				edit: ListingEditor,
				save: () => null, // uses view.php
			} );
		}
	}
};
