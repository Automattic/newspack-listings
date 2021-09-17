/**
 * WordPress dependencies
 */
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
import { FeaturedListings } from './featured-listings';
import { isListing } from './utils';
import './style.scss';

const { post_type: postType } = window?.newspack_listings_data;

/**
 * Register Curated List blocks. Don't register if we're in a listing already
 * (to avoid possibly infinitely nesting lists within list items).
 */
if ( isListing() ) {
	const { post_types: postTypes } = window?.newspack_listings_data || {};

	// If we don't have a post type, we're probably not in a post editor, so we don't need to register the post editor sidebars.
	if ( postType ) {
		// Register plugin editor settings.
		registerPlugin( 'newspack-listings-editor', {
			render: Sidebar,
			icon: null,
		} );
		// Register plugin editor settings.
		registerPlugin( 'newspack-listings-featured', {
			render: FeaturedListings,
			icon: null,
		} );
	}

	// Register Event Dates block if we're editing an Event.
	if ( isListing( postTypes.event.name ) ) {
		registerEventDatesBlock();
	}

	// Register Price block if we're editing a Marketplace listing.
	if ( isListing( postTypes.marketplace.name ) ) {
		registerPriceBlock();
	}
} else {
	setCustomCategory();
	registerCuratedListBlock();
	registerListContainerBlock();
	registerListingBlock();
}

// If we don't have a post type, we're probably not in a post editor, so we don't need to register the post taxonomy sidebars.
if ( postType ) {
	// Register plugin editor settings.
	registerPlugin( 'newspack-listings-shadow-taxonomies', {
		render: ShadowTaxonomies,
		icon: null,
	} );
}
