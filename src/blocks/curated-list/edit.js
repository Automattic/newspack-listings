/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	ButtonGroup,
	PanelBody,
	PanelRow,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getCuratedListClasses } from '../../editor/utils';

const CuratedListEditorComponent = ( {
	attributes,
	canUseMapBlock,
	className,
	clientId,
	innerBlocks,
	insertBlock,
	removeBlock,
	setAttributes,
	updateBlockAttributes,
} ) => {
	const [ locations, setLocations ] = useState( [] );
	const {
		showNumbers,
		showMap,
		showSortByDate,
		showAuthor,
		showExcerpt,
		showImage,
		showCaption,
		minHeight,
		showCategory,
		mediaPosition,
		typeScale,
		imageScale,
		mobileStack,
		textColor,
	} = attributes;

	const list = innerBlocks.find(
		innerBlock => innerBlock.name === 'newspack-listings/list-container'
	);
	const hasMap = innerBlocks.find( innerBlock => innerBlock.name === 'jetpack/map' );
	const classes = getCuratedListClasses( className, attributes );

	// Update locations in component state. This lets us keep the map block in sync with listing items.
	useEffect(() => {
		// Only build locations array if we have any listings, and the Jetpack Maps block exists.
		const blockLocations =
			canUseMapBlock && list
				? list.innerBlocks.reduce( ( acc, innerBlock ) => {
						if ( innerBlock.attributes.locations && 0 < innerBlock.attributes.locations.length ) {
							innerBlock.attributes.locations.map( location => acc.push( location ) );
						}
						return acc;
				  }, [] )
				: [];

		setLocations( blockLocations );
	}, [ JSON.stringify( list ) ]);

	// Create, update, or remove map when showMap attribute or locations change.
	useEffect(() => {
		// Don't bother if the Jetpack Maps block doesn't exist.
		if ( ! canUseMapBlock ) {
			return;
		}

		// If showMap toggle is enabled, update the existing map or create a new one.
		if ( showMap ) {
			if ( hasMap ) {
				// If we already have a map, update it.
				updateBlockAttributes( hasMap.clientId, { points: locations } );
			} else {
				// Don't add a new map unless we have some locations to show.
				if ( 0 === locations.length ) {
					return;
				}

				// Create a new map at the top of the list.
				const newBlock = createBlock( 'jetpack/map', {
					points: locations,
				} );

				insertBlock( newBlock, 0, clientId );
			}
		} else if ( hasMap ) {
			// If disabling the showMap toggle, remove the existing map.
			removeBlock( hasMap.clientId );
		}
	}, [ showMap, JSON.stringify( locations ) ]);

	const imageSizeOptions = [
		{
			value: 1,
			label: /* translators: label for small size option */ __( 'Small', 'newspack-listings' ),
			shortName: /* translators: abbreviation for small size */ __( 'S', 'newspack-listings' ),
		},
		{
			value: 2,
			label: /* translators: label for medium size option */ __( 'Medium', 'newspack-listings' ),
			shortName: /* translators: abbreviation for medium size */ __( 'M', 'newspack-listings' ),
		},
		{
			value: 3,
			label: /* translators: label for large size option */ __( 'Large', 'newspack-listings' ),
			shortName: /* translators: abbreviation for large size */ __( 'L', 'newspack-listings' ),
		},
		{
			value: 4,
			label: /* translators: label for extra large size option */ __(
				'Extra Large',
				'newspack-listings'
			),
			shortName: /* translators: abbreviation for extra large size */ __(
				'XL',
				'newspack-listings'
			),
		},
	];

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

					{ canUseMapBlock && (
						<PanelRow>
							<ToggleControl
								label={ __( 'Show map', 'newspack-listings' ) }
								checked={ showMap }
								onChange={ () => setAttributes( { showMap: ! showMap } ) }
							/>
						</PanelRow>
					) }

					<PanelRow>
						<ToggleControl
							label={ __( 'Show sort-by-date UI', 'newspack-listings' ) }
							checked={ showSortByDate }
							onChange={ () => setAttributes( { showSortByDate: ! showSortByDate } ) }
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody title={ __( 'Featured Image Settings', 'newspack-listings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Featured Image', 'newspack-listings' ) }
							checked={ showImage }
							onChange={ () => setAttributes( { showImage: ! showImage } ) }
						/>
					</PanelRow>

					{ showImage && (
						<Fragment>
							<PanelRow>
								<ToggleControl
									label={ __( 'Show Featured Image Caption', 'newspack-listings' ) }
									checked={ showCaption }
									onChange={ () => setAttributes( { showCaption: ! showCaption } ) }
								/>
							</PanelRow>
							<SelectControl
								label={ __( 'Featured Image Position', 'newspack-listings' ) }
								value={ mediaPosition }
								onChange={ value => setAttributes( { mediaPosition: value } ) }
								options={ [
									{ label: __( 'Top', 'newspack-listings' ), value: 'top' },
									{ label: __( 'Left', 'newspack-listings' ), value: 'left' },
									{ label: __( 'Right', 'newspack-listings' ), value: 'right' },
								] }
							/>
						</Fragment>
					) }

					{ showImage && mediaPosition !== 'top' && mediaPosition !== 'behind' && (
						<Fragment>
							<PanelRow>
								<ToggleControl
									label={ __( 'Stack on mobile', 'newspack-listings' ) }
									checked={ mobileStack }
									onChange={ () => setAttributes( { mobileStack: ! mobileStack } ) }
								/>
							</PanelRow>
							<BaseControl
								label={ __( 'Featured Image Size', 'newspack-listings' ) }
								id="newspackfeatured-image-size"
							>
								<PanelRow>
									<ButtonGroup
										id="newspackfeatured-image-size"
										aria-label={ __( 'Featured Image Size', 'newspack-listings' ) }
									>
										{ imageSizeOptions.map( option => {
											const isCurrent = imageScale === option.value;
											return (
												<Button
													isLarge
													isPrimary={ isCurrent }
													aria-pressed={ isCurrent }
													aria-label={ option.label }
													key={ option.value }
													onClick={ () => setAttributes( { imageScale: option.value } ) }
												>
													{ option.shortName }
												</Button>
											);
										} ) }
									</ButtonGroup>
								</PanelRow>
							</BaseControl>
						</Fragment>
					) }

					{ showImage && mediaPosition === 'behind' && (
						<RangeControl
							label={ __( 'Minimum height', 'newspack-listings' ) }
							help={ __(
								"Sets a minimum height for the block, using a percentage of the screen's current height.",
								'newspack-listings'
							) }
							value={ minHeight }
							onChange={ _minHeight => setAttributes( { minHeight: _minHeight } ) }
							min={ 0 }
							max={ 100 }
							required
						/>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Post Control Settings', 'newspack-listings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Author', 'newspack-listings' ) }
							checked={ showAuthor }
							onChange={ () => setAttributes( { showAuthor: ! showAuthor } ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Excerpt', 'newspack-listings' ) }
							checked={ showExcerpt }
							onChange={ () => setAttributes( { showExcerpt: ! showExcerpt } ) }
						/>
					</PanelRow>
					<RangeControl
						className="type-scale-slider"
						label={ __( 'Type Scale', 'newspack-listings' ) }
						value={ typeScale }
						onChange={ _typeScale => setAttributes( { typeScale: _typeScale } ) }
						min={ 1 }
						max={ 10 }
						beforeIcon="editor-textcolor"
						afterIcon="editor-textcolor"
						required
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Color Settings', 'newspack-listings' ) }
					initialOpen={ true }
					colorSettings={ [
						{
							value: textColor,
							onChange: value => setAttributes( { textColor: value } ),
							label: __( 'Text Color', 'newspack-listings' ),
						},
					] }
				/>
				<PanelBody title={ __( 'Post Meta Settings', 'newspack-listings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Category', 'newspack-listings' ) }
							checked={ showCategory }
							onChange={ () => setAttributes( { showCategory: ! showCategory } ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div className={ classes.join( ' ' ) }>
				<span className="newspack-listings__curated-list-label">
					{ __( 'Curated List', 'newspack-listings' ) }
				</span>
				<InnerBlocks
					allowedBlocks={ [ 'jetpack/map', 'newspack-listings/list-container' ] }
					template={ [ [ 'newspack-listings/list-container' ] ] } // Start with an empty list only.
					templateInsertUpdatesSelection={ false }
					renderAppender={ () => null } // We want to discourage editors from adding blocks in this top-level wrapper, but we can't lock the template because we still need to be able to programmatically add or remove map blocks.
				/>
			</div>
		</div>
	);
};

const mapStateToProps = ( select, ownProps ) => {
	const { getBlocksByClientId } = select( 'core/block-editor' );
	const { getBlockType } = select( 'core/blocks' );
	const innerBlocks = getBlocksByClientId( ownProps.clientId )[ 0 ].innerBlocks || [];
	const canUseMapBlock = !! getBlockType( 'jetpack/map' ); // Check for existence of Jetpack Map block before enabling location-based features.

	return {
		canUseMapBlock,
		innerBlocks,
	};
};

const mapDispatchToProps = dispatch => {
	const { insertBlock, removeBlock, updateBlockAttributes } = dispatch( 'core/block-editor' );

	return {
		insertBlock,
		removeBlock,
		updateBlockAttributes,
	};
};

export const CuratedListEditor = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( CuratedListEditorComponent );
