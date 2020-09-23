/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import {
	Button,
	ButtonGroup,
	Notice,
	PanelBody,
	PanelRow,
	RangeControl,
	ToggleControl,
	BaseControl,
} from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { Fragment } from '@wordpress/element';

const CuratedListEditorComponent = ( { attributes, clientId, getBlock, setAttributes } ) => {
	const {
		showNumbers,
		showMap,
		showSortByDate,
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
		showSubtitle,
	} = attributes;

	const classes = [ 'newspack-listings__curated-list-container' ];
	if ( showNumbers ) classes.push( 'show-numbers' );
	if ( showMap ) classes.push( 'show-map' );
	if ( showSortByDate ) classes.push( 'has-sort-by-date-ui' );

	const innerBlocks = getBlock( clientId ).innerBlocks;

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

	const subtitleIsSupportedInTheme =
		typeof window === 'object' &&
		window.newspackIsPostSubtitleSupported &&
		window.newspackIsPostSubtitleSupported.post_subtitle;

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
				<PanelBody title={ __( 'Featured Image Settings', 'newspack-listings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Featured Image', 'newspack-listings' ) }
							checked={ showImage }
							onChange={ () => setAttributes( { showImage: ! showImage } ) }
						/>
					</PanelRow>

					{ showImage && (
						<PanelRow>
							<ToggleControl
								label={ __( 'Show Featured Image Caption', 'newspack-listings' ) }
								checked={ showCaption }
								onChange={ () => setAttributes( { showCaption: ! showCaption } ) }
							/>
						</PanelRow>
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
					{ subtitleIsSupportedInTheme && (
						<PanelRow>
							<ToggleControl
								label={ __( 'Show Subtitle', 'newspack-listings' ) }
								checked={ showSubtitle }
								onChange={ () => setAttributes( { showSubtitle: ! showSubtitle } ) }
							/>
						</PanelRow>
					) }
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
				<span className="newspack-listings__curated-list-container-label">
					{ __( 'Curated List', 'newspack-listings' ) }
				</span>
				{ 0 === innerBlocks.length && (
					<Notice className="newspack-listings__info" status="info" isDismissible={ false }>
						{ __( 'This list is empty. Click the [+] button to add some listings.' ) }
					</Notice>
				) }
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

const mapStateToProps = select => {
	const { getBlock } = select( 'core/block-editor' );

	return {
		getBlock,
	};
};

export const CuratedListEditor = withSelect( mapStateToProps )( CuratedListEditorComponent );
