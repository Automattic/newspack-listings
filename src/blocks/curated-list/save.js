/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { getCuratedListClasses } from '../../editor/utils';

export const CuratedList = ( { attributes, className } ) => {
	const classes = getCuratedListClasses( className, attributes );

	return (
		<div className={ classes.join( ' ' ) }>
			<InnerBlocks.Content />
		</div>
	);
};
