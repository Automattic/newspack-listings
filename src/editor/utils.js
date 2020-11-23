/**
 * Util functions for Newspack Listings.
 */

/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Event, Generic, Marketplace, Place } from '../svg';

/**
 * Check if the current post in the editor is a listing CPT.
 *
 * @return {boolean} Whether or not the current post is a listing CPT.
 */
export const isListing = () => {
	if ( ! window.newspack_listings_data ) {
		return false;
	}

	const { post_type, post_types } = window.newspack_listings_data;

	for ( const slug in post_types ) {
		if ( post_types.hasOwnProperty( slug ) && post_type === post_types[ slug ].name ) {
			return true;
		}
	}

	return false;
};

/**
 * Get array of class names for Curated List, based on attributes.
 *
 * @param {string} className The base class name for the block.
 * @param {Object} attributes Block attributes.
 *
 * @return {Array} Array of class names for the block.
 */
export const getCuratedListClasses = ( className, attributes ) => {
	const {
		showNumbers,
		showMap,
		showSortUi,
		showImage,
		mediaPosition,
		typeScale,
		imageScale,
	} = attributes;

	const classes = [ className, 'newspack-listings__curated-list' ];

	if ( showNumbers ) classes.push( 'show-numbers' );
	if ( showMap ) classes.push( 'show-map' );
	if ( showSortUi ) classes.push( 'has-sort-ui' );
	if ( showImage ) {
		classes.push( 'show-image' );
		classes.push( `media-position-${ mediaPosition }` );
		classes.push( `media-size-${ imageScale }` );
	}

	classes.push( `type-scale-${ typeScale }` );

	return classes;
};

/**
 * Hook to tell us whether the current render is the initial render
 * (immediately after mount, with default props) or a subsequent render.
 * Useful so we don't fire side effects before block attributes are ready.
 *
 * return {boolean} True if this is the initial render, false if subsequent.
 */
export const useDidMount = () => {
	const didMount = useRef( true );

	useEffect(() => {
		didMount.current = false;
	}, []);

	return didMount.current;
};

/**
 * Generic utility to capitalize a given string.
 *
 * @param {string} str String to capitalize.
 * @return {string} Same string, with first letter capitalized.
 */
export const capitalize = str => str[ 0 ].toUpperCase() + str.slice( 1 );

/**
 * Map listing type icons to listing type slugs.
 *
 * @param {string} listingTypeSlug Slug of the listing type to get an icon for.
 *               One of: event, generic, marketplace, place
 * @return {function} SVG component for the matching icon.
 */
export const getIcon = listingTypeSlug => {
	switch ( listingTypeSlug ) {
		case 'event':
			return Event;
		case 'marketplace':
			return Marketplace;
		case 'place':
			return Place;
		default:
			return Generic;
	}
};
