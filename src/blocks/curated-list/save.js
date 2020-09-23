/**
 * Curated List block front-end output.
 */

/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

export const CuratedList = ( { attributes } ) => {
	const {
		showNumbers,
		showMap,
		showSortByDate,
		// showExcerpt,
		// showImage,
		// showCaption,
		// minHeight,
		// showCategory,
		// mediaPosition,
		// typeScale,
		// imageScale,
		// mobileStack,
		// textColor,
		// showSubtitle,
	} = attributes;

	const classes = [ 'newspack-listings__curated-list-container' ];
	if ( showNumbers ) classes.push( 'show-numbers' );
	if ( showMap ) classes.push( 'show-map' );
	if ( showSortByDate ) classes.push( 'has-sort-by-date-ui' );

	// TODO: implement map functionality.
	return (
		<ol className={ classes.join( ' ' ) }>
			<InnerBlocks.Content />
		</ol>
	);
};
