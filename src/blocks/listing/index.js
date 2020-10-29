/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { registerBlockType } from '@wordpress/blocks';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import './editor.scss';
import { ListingEditor } from './edit';
import metadata from './block.json';
import parentData from '../curated-list/block.json';

const parentAttributes = parentData.attributes;
const { attributes, category } = metadata;
const { post_types } = window.newspack_listings_data;

export const registerListingBlock = async () => {
	for ( const listingType in post_types ) {
		if ( post_types.hasOwnProperty( listingType ) ) {
			let postCount = 0;

			// We only want to show blocks if there are any listings
			try {
				const posts = await apiFetch( {
					path: addQueryArgs( '/wp/v2/' + post_types[ listingType ], {
						per_page: 1,
						status: 'publish',
					} ),
					parse: false,
				} );

				postCount = posts.headers.get( 'X-WP-Total' );
			} catch ( e ) {
				/* eslint-disable no-console */
				console.error( e );
			}

			registerBlockType( `newspack-listings/${ listingType }`, {
				title: listingType.charAt( 0 ).toUpperCase() + listingType.slice( 1 ),
				icon: 'list-view',
				category,
				parent: [ 'newspack-listings/list-container' ],
				keywords: [
					__( 'lists', 'newspack-listings' ),
					__( 'listings', 'newspack-listings' ),
					__( 'latest', 'newspack-listings' ),
				],

				// Combine attributes with parent attributes, so parent can pass data to InnerBlocks without relying on contexts.
				attributes: Object.assign( attributes, parentAttributes ),

				// Hide from block inserter menus if there are no posts of this type.
				supports: {
					inserter: 0 < postCount,
				},

				edit: ListingEditor,
				save: () => null, // uses view.php
			} );
		}
	}
};
