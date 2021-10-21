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

// Apologies for the extreme hackiness that follows, but there doesn't seem to be a WP-supported way to do these things.
if ( isListingCustomer ) {
	// Wait until the editor itself is mounted before doing anything. This doesn't mean all editor UI elements will be mounted, though.
	// eslint-disable-next-line no-unused-expressions
	window._wpLoadBlockEditor?.then( () => {
		let editorMounted = false;

		// We need to wait until the editor UI elements we need to edit exist in the DOM.
		// Keep trying every second until we can query them.
		const intervalId = window.setInterval( () => {
			const editor = document.getElementById( 'editor' );

			// Are the UI elements we need in the DOM yet? If so, we can stop the interval.
			if ( editor ) {
				editorMounted = true;
				window.clearInterval( intervalId );
			}

			if ( editorMounted ) {
				// If the currently logged-in user is a self-serve listings customer, change the WP logo link shown
				// in the editor while in full-screen mode to redirect back to the "My Account" page instead of the
				// (inaccessible to customers) post type dashboard page.
				// If the editor starts in full-screen mode, this element will exist as soon as the editor mounts.
				let closeFullScreenButton = editor.querySelector( '.edit-post-fullscreen-mode-close' );
				if ( closeFullScreenButton ) {
					closeFullScreenButton.setAttribute( 'href', '/wp-admin/profile.php' );
				}

				// Since we're running this outside of React, we can use a MutationObserver to run a callback whenever
				// the child elements of the editor element change (mutate).
				const observer = new MutationObserver( mutationsList => {
					for ( const mutation of mutationsList ) {
						if ( 'childList' === mutation.type ) {
							// The full-screen close button might be unmounted and remounted if switching between full screen modes.
							if ( mutation.target.classList.contains( 'edit-post-header' ) ) {
								closeFullScreenButton = editor.querySelector( '.edit-post-fullscreen-mode-close' );
								if ( closeFullScreenButton ) {
									closeFullScreenButton.setAttribute( 'href', '/wp-admin/profile.php' );
								}
							}

							// Also remove the "Mapbox Access Token" sidebar panel from the jetpack/map block.
							// We can't really avoid exposing the API token (which is public anyway), but we can
							// at least try to prevent customers from changing or unsetting it, which affects all users.
							// Note: I hate this, but WP provides no API For "properly" suppressing block sidebars.
							if ( mutation.target.classList.contains( 'components-panel' ) ) {
								const sidebar = editor.querySelector( '.components-panel' );
								if ( sidebar ) {
									const panels = Array.from(
										sidebar.querySelectorAll( '.components-panel__body' )
									);

									panels.forEach( panel => {
										const title = panel.querySelector( '.components-panel__body-title' );

										// No other way to identify a particular sidebar panel, so this will only work for English-language sites.
										if ( 'Mapbox Access Token' === title.textContent ) {
											panel.style.display = 'none';
										}
									} );
								}
							}
						}
					}
				} );

				observer.observe( editor, { childList: true, subtree: true } );
			}
		}, 1000 );
	} );
}

// If we don't have a post type, we're probably not in a post editor, so we don't need to register the post taxonomy sidebars.
if ( postType ) {
	// Register plugin editor settings.
	registerPlugin( 'newspack-listings-shadow-taxonomies', {
		render: ShadowTaxonomies,
		icon: null,
	} );
}
