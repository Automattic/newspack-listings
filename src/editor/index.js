/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { Sidebar } from './sidebar';
import { registerListingBlock } from '../blocks';

/**
 * Register plugin editor settings.
 */
registerPlugin( 'newspack-listings-editor', {
	render: Sidebar,
	icon: null,
} );

/**
 * Register blocks.
 */
registerListingBlock();
