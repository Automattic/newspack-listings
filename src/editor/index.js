/**
 * Internal dependencies
 */
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
if ( ! isListing() ) {
	setCustomCategory();
	registerCuratedListBlock();
	registerListContainerBlock();
	registerListingBlock();
}
