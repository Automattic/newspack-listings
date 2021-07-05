/**
 * Util functions for Newspack Listings.
 */

/**
 * WordPress dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { Icon, calendar, mapMarker, postList, store } from '@wordpress/icons';

/**
 * Check if the current post in the editor is a listing CPT.
 *
 * @param {string|null} listingType (Optional) If given, check if the current post is this exact listing type
 * @return {boolean} Whether or not the current post is a listing CPT.
 */
export const isListing = ( listingType = null ) => {
	if ( ! window.newspack_listings_data ) {
		return false;
	}

	const { post_type, post_types } = window.newspack_listings_data;

	// If passed a listingType arg, just check whether it matches the current post type.
	if ( null !== listingType ) {
		return listingType === post_type;
	}

	// Otherwise, check whether the current post type is any listing type.
	for ( const slug in post_types ) {
		if ( post_types.hasOwnProperty( slug ) && post_type === post_types[ slug ].name ) {
			return true;
		}
	}

	return false;
};

/**
 * Given a post type string, get the human-readable label for the post type.
 *
 * @param {string} postType Name of the post type.
 * @return {string} The label of the post type, or the post type itself if we can't determine.
 */
export const getPostTypeLabel = postType => {
	if ( ! window.newspack_listings_data ) {
		return capitalize( postTypes );
	}

	const { post_types: postTypes } = window.newspack_listings_data;

	// Check whether the given taxonomy is a Listings shadow taxonomy.
	for ( const slug in postTypes ) {
		if ( postTypes.hasOwnProperty( slug ) && postType === postTypes[ slug ].name ) {
			return postTypes[ slug ].label;
		}
	}

	return capitalize( postType );
};

/**
 * Check if the given taxonomy is a Listings shadow taxonomy.
 *
 * @param {string} taxonomyName Name of the taxonomy.
 * @return {boolean} Whether or not the given taxonomy is a Listings shadow taxonomy.
 */
export const isShadowTaxonomy = taxonomyName => {
	if ( ! window.newspack_listings_data ) {
		return false;
	}

	const { taxonomies } = window.newspack_listings_data;

	// Check whether the given taxonomy is a Listings shadow taxonomy.
	for ( const slug in taxonomies ) {
		if ( taxonomies.hasOwnProperty( slug ) && taxonomyName === taxonomies[ slug ].name ) {
			return true;
		}
	}

	return false;
};

/**
 * Given a shadow taxonomy name, get the human-readable label for the taxonomy.
 *
 * @param {string} taxonomyName Name of the taxonomy.
 * @return {string} The label of the taxonomy, or the taxonomy name itself if we can't determine.
 */
export const getTaxonomyLabel = taxonomyName => {
	if ( ! window.newspack_listings_data ) {
		return capitalize( taxonomyName );
	}

	const { taxonomies } = window.newspack_listings_data;

	// Check whether the given taxonomy is a Listings shadow taxonomy.
	for ( const slug in taxonomies ) {
		if ( taxonomies.hasOwnProperty( slug ) && taxonomyName === taxonomies[ slug ].name ) {
			return taxonomies[ slug ].label;
		}
	}

	return capitalize( taxonomyName );
};

/**
 * Given a shadow taxonomy name, find the correponding post type it shadows.
 *
 * @param {string} taxonomyName Name of the taxonomy.
 * @return {string|boolean} The post type the given taxonomy shadows, or false if none.
 */
export const getPostTypeByTaxonomy = taxonomyName => {
	if ( ! window.newspack_listings_data ) {
		return false;
	}

	const { post_types: postTypes, taxonomies } = window.newspack_listings_data;

	// Check whether the given taxonomy is a Listings shadow taxonomy.
	for ( const slug in taxonomies ) {
		if ( taxonomies.hasOwnProperty( slug ) && taxonomyName === taxonomies[ slug ].name ) {
			return postTypes[ slug ].name;
		}
	}

	return false;
};

/**
 * Given a post type, get the corresopnding shadow taxonomy that shadows it.
 *
 * @param {string|null} postType (Optional) Post type to check. If not given, will extract from global data.
 * @return {Object|boolean} The taxonomy that shadows the given post type, or false if none.
 */
export const getTaxonomyForPostType = ( postType = null ) => {
	if ( ! window.newspack_listings_data ) {
		return false;
	}

	const { post_type, post_types: postTypes, taxonomies } = window.newspack_listings_data;
	const postTypeToCheck = postType || post_type;

	for ( const slug in postTypes ) {
		if ( postTypes[ slug ].name === postTypeToCheck ) {
			return taxonomies[ slug ];
		}
	}

	return false;
};

/**
 * Convert hex color to RGB.
 * From https://stackoverflow.com/questions/5623838/rgb-to-hex-and-hex-to-rgb
 *
 * @param {string} hex Color in HEX format
 * @return {Array} RGB values, e.g. [red, green, blue]
 */
const hexToRGB = hex =>
	hex
		.replace( /^#?([a-f\d])([a-f\d])([a-f\d])$/i, ( m, r, g, b ) => '#' + r + r + g + g + b + b )
		.substring( 1 )
		.match( /.{2}/g )
		.map( x => parseInt( x, 16 ) );

/**
 * Get contrast ratio of the given backgroundColor compared to black.
 *
 * @param {string} backgroundColor Color HEX value to compare with black.
 * @return {number} Contrast ratio vs. black.
 */
export const getContrastRatio = backgroundColor => {
	const blackColor = '#000';
	const backgroundColorRGB = hexToRGB( backgroundColor );
	const blackRGB = hexToRGB( blackColor );

	const l1 =
		0.2126 * Math.pow( backgroundColorRGB[ 0 ] / 255, 2.2 ) +
		0.7152 * Math.pow( backgroundColorRGB[ 1 ] / 255, 2.2 ) +
		0.0722 * Math.pow( backgroundColorRGB[ 2 ] / 255, 2.2 );
	const l2 =
		0.2126 * Math.pow( blackRGB[ 0 ] / 255, 2.2 ) +
		0.7152 * Math.pow( blackRGB[ 1 ] / 255, 2.2 ) +
		0.0722 * Math.pow( blackRGB[ 2 ] / 255, 2.2 );

	return l1 > l2
		? parseInt( ( l1 + 0.05 ) / ( l2 + 0.05 ) )
		: parseInt( ( l2 + 0.05 ) / ( l1 + 0.05 ) );
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
		backgroundColor,
		hasDarkBackground,
		queryMode,
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
	if ( backgroundColor ) {
		if ( hasDarkBackground ) {
			classes.push( 'has-dark-background' );
		}
		classes.push( 'has-background-color' );
	}
	if ( queryMode ) {
		classes.push( 'query-mode' );
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
 * @return {Function} SVG component for the matching icon.
 */
export const getIcon = listingTypeSlug => {
	switch ( listingTypeSlug ) {
		case 'event':
			return <Icon icon={ calendar } />;
		case 'marketplace':
			return <Icon icon={ store } />;
		case 'place':
			return <Icon icon={ mapMarker } />;
		default:
			return <Icon icon={ postList } />;
	}
};
