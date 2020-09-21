/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, ToggleControl } from '@wordpress/components';

export const CuratedListEditor = ( { attributes, setAttributes } ) => {
	const { showNumbers, showMap, showSortByDate } = attributes;

	const classes = [ 'newspack-listings__curated-list-container' ];
	if ( showNumbers ) classes.push( 'show-numbers' );
	if ( showMap ) classes.push( 'show-map' );
	if ( showSortByDate ) classes.push( 'has-sort-by-date-ui' );

	return (
		<div className="newspack-listings__curated-list-editor">
			<InspectorControls>
				<PanelBody title={ __( 'Curated List Settings', 'newspack-listings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show list item numbers', 'newspack-listings' ) }
							checked={ showNumbers }
							onChange={ () => setAttributes( { showNumbers: ! showNumbers } ) }
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ __( 'Show map', 'newspack-listings' ) }
							checked={ showMap }
							onChange={ () => setAttributes( { showMap: ! showMap } ) }
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ __( 'Show sort-by-date UI', 'newspack-listings' ) }
							checked={ showSortByDate }
							onChange={ () => setAttributes( { showSortByDate: ! showSortByDate } ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<div className={ classes.join( ' ' ) }>
				<span className="newspack-listings__curated-list-container-label">
					{ __( 'Curated List', 'newspack-listings' ) }
				</span>
				<InnerBlocks
					allowedBlocks={ [
						'newspack-listings/event',
						'newspack-listings/generic',
						'newspack-listings/marketplace',
						'newspack-listings/place',
					] }
				/>
			</div>
		</div>
	);
};
