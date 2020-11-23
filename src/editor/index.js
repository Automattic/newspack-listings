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
	registerListingBlock,
	setCustomCategory,
} from '../blocks';
import { isListing } from './utils';

/**
 * Register Curated List blocks. Don't register if we're in a listing already
 * (to avoid possibly infinitely nesting lists within list items).
 */
if ( isListing() ) {
	// Register plugin editor settings.
	registerPlugin( 'newspack-listings-editor', {
		render: Sidebar,
		icon: null,
	} );
} else {
	setCustomCategory();
	registerCuratedListBlock();
	registerListContainerBlock();
	registerListingBlock();
}
