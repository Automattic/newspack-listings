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
import './style.scss';

/**
 * Register blocks.
 */
setCustomCategory();
registerCuratedListBlock();
registerListContainerBlock();
registerListingBlock();

/**
 * Register sidebar editor settings.
 */
registerPlugin( 'newspack-listings-editor', {
	render: Sidebar,
	icon: null,
} );
