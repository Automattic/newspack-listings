/**
 * Internal dependencies
 */
import {
	registerCuratedListBlock,
	registerListContainerBlock,
	registerListingBlock,
	setCustomCategory,
} from '../blocks';
import './style.scss';

/**
 * Register blocks.
 */
setCustomCategory();
registerCuratedListBlock();
registerListContainerBlock();
registerListingBlock();
