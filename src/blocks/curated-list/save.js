/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

export const CuratedList = ( { attributes, className } ) => {
	const { showNumbers, showMap, showSortByDate } = attributes;

	const classes = [ className, 'newspack-listings__curated-list' ];
	if ( showNumbers ) classes.push( 'show-numbers' );
	if ( showMap ) classes.push( 'show-map' );
	if ( showSortByDate ) classes.push( 'has-sort-by-date-ui' );

	return (
		<div className={ classes.join( ' ' ) }>
			<InnerBlocks.Content />
		</div>
	);
};
