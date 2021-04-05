/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { Sidebar } from './sidebar';
import {
	registerCuratedListBlock,
	registerListContainerBlock,
	registerEventDatesBlock,
	registerListingBlock,
	registerPriceBlock,
	setCustomCategory,
} from '../blocks';
import { ShadowTaxonomies } from './shadow-taxonomies';
import { isListing } from './utils';

/**
 * Register Curated List blocks. Don't register if we're in a listing already
 * (to avoid possibly infinitely nesting lists within list items).
 */
if ( isListing() ) {
	const { post_types } = window.newspack_listings_data || {};

	// Register plugin editor settings.
	registerPlugin( 'newspack-listings-editor', {
		render: Sidebar,
		icon: null,
	} );

	// Register Event Dates block if we're editing an Event.
	if ( isListing( post_types.event.name ) ) {
		registerEventDatesBlock();
	}

	// Register Price block if we're editing a Marketplace listing.
	if ( isListing( post_types.marketplace.name ) ) {
		registerPriceBlock();
	}

	// Filter taxonomy UI for listing shadow taxonomies.
	addFilter( 'editor.PostTaxonomyType', 'newspack-listings', ShadowTaxonomies );
} else {
	setCustomCategory();
	registerCuratedListBlock();
	registerListContainerBlock();
	registerListingBlock();
}
