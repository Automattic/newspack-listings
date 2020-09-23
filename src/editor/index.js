/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { Sidebar } from './sidebar';
import { registerListingBlock } from '../blocks';
import './style.scss';

/**
 * Register blocks.
 */
registerListingBlock();

/**
 * Register sidebar editor settings.
 */
registerPlugin( 'newspack-listings-editor', {
	render: Sidebar,
	icon: null,
} );
