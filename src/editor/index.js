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
	registerSelfServeListingsBlock,
	setCustomCategory,
} from '../blocks';
import { ShadowTaxonomies } from './shadow-taxonomies';
import { isListing } from './utils';
import './style.scss';

const {
	post_type: postType,
	post_types: postTypes,
	self_serve_enabled: selfServeEnabled,
	is_listing_customer: isListingCustomer = false,
} = window?.newspack_listings_data;

/**
 * Register Curated List blocks. Don't register if we're in a listing already
 * (to avoid possibly infinitely nesting lists within list items).
 */
if ( isListing() ) {
	// If we don't have a post type, we're probably not in a post editor, so we don't need to register the post editor sidebars.
	if ( postType ) {
		// Register plugin editor settings.
		registerPlugin( 'newspack-listings-editor', {
			render: Sidebar,
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

	if ( selfServeEnabled ) {
		registerSelfServeListingsBlock();
	}
}

// If the currently logged-in user is a self-serve listings customer, change the WP logo link shown in the editor while in full-screen mode to redirect back to the "My Account" page. Apologies for the extreme hackiness but there doesn't seem to be any supported way to do this via WP hooks.
if ( isListingCustomer ) {
	// eslint-disable-next-line no-unused-expressions
	window._wpLoadBlockEditor?.then(
		window.setTimeout( () => {
			const closeFullScreenButton = document.querySelector( '.edit-post-fullscreen-mode-close' );

			if ( closeFullScreenButton ) {
				closeFullScreenButton.setAttribute( 'href', '/wp-admin/profile.php' );
			}
		}, 1000 )
	);
}

// If we don't have a post type, we're probably not in a post editor, so we don't need to register the post taxonomy sidebars.
if ( postType ) {
	// Register plugin editor settings.
	registerPlugin( 'newspack-listings-shadow-taxonomies', {
		render: ShadowTaxonomies,
		icon: null,
	} );
}
