/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * WorPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	__experimentalBlockVariationPicker as BlockVariationPicker,
	InnerBlocks,
	InspectorControls,
	PanelColorSettings,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import {
	BaseControl,
	Button,
	ButtonGroup,
	Notice,
	PanelBody,
	PanelRow,
	Placeholder,
	RangeControl,
	SelectControl,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { Listing } from '../listing/listing';
import { SidebarQueryControls } from '../../components';
import { List, Query, Specific } from '../../svg';
import { getCuratedListClasses, useDidMount } from '../../editor/utils';

const CuratedListEditorComponent = ( {
	attributes,
	canUseMapBlock,
	className,
	clientId,
	innerBlocks,
	insertBlocks,
	removeBlocks,
	setAttributes,
	updateBlockAttributes,
} ) => {
	const [ error, setError ] = useState( null );
	const [ isFetching, setIsFetching ] = useState( false );
	const [ locations, setLocations ] = useState( [] );
	const {
		showNumbers,
		showMap,
		showAuthor,
		showExcerpt,
		showImage,
		showCaption,
		minHeight,
		showCategory,
		showTags,
		mediaPosition,
		typeScale,
		imageScale,
		textColor,
		startup,
		queryMode,
		queryOptions,
		queriedListings,
		showLoadMore,
		loadMoreText,
	} = attributes;

	const isEmpty = !! window.newspack_listings_data.no_listings || false;
	const list = innerBlocks.find(
		innerBlock => innerBlock.name === 'newspack-listings/list-container'
	);
	const hasMap = innerBlocks.find( innerBlock => innerBlock.name === 'jetpack/map' );
	const classes = getCuratedListClasses( className, attributes );
	const initialRender = useDidMount();

	/**
	 * Use current query options to get listing posts.
	 *
	 * @param {Object} query Query args.
	 * @return {void}
	 */
	const fetchPosts = async query => {
		if ( isFetching || ! queryMode ) {
			return;
		}

		setIsFetching( true );

		try {
			setError( null );
			const posts = await apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/listings', {
					query,
					_fields: 'id,title,author,category,tags,excerpt,media,meta,type',
				} ),
			} );

			setAttributes( { queriedListings: posts } );

			if ( 0 === posts.length ) {
				throw 'No posts matching query options. Try selecting different or less specific query options.';
			}
		} catch ( e ) {
			setError( e );
		}

		setIsFetching( false );
	};

	/**
	 * Debounced version of fetchPosts to minimize consecutive executions.
	 */
	const debouncedFetchPosts = debounce( fetchPosts, 500 );

	/**
	 * If changing query options, fetch listing posts that match the query.
	 */
	useEffect(() => {
		if ( initialRender ) {
			fetchPosts( queryOptions );
		} else {
			debouncedFetchPosts( queryOptions );
		}
	}, [ JSON.stringify( queryOptions ), queryMode ]);

	/**
	 * Update locations in component state. This lets us keep the map block in sync with listing items.
	 */
	useEffect(() => {
		if ( ! canUseMapBlock ) {
			return;
		}

		let newLocations = [];

		// Only build locations array if we have any listings, and the Jetpack Maps block exists.
		if ( queryMode ) {
			newLocations = queriedListings.reduce( ( acc, queriedListing ) => {
				if ( queriedListing.meta && queriedListing.meta.newspack_listings_locations ) {
					queriedListing.meta.newspack_listings_locations.map( location => {
						if ( isValidLocation( location ) ) {
							acc.push( location );
						}

						return acc;
					} );
				}

				return acc;
			}, [] );
		} else {
			newLocations = list
				? list.innerBlocks.reduce( ( acc, innerBlock ) => {
						if ( innerBlock.attributes.locations && 0 < innerBlock.attributes.locations.length ) {
							innerBlock.attributes.locations.map( location => {
								if ( isValidLocation( location ) ) {
									acc.push( location );
								}

								return acc;
							} );
						}
						return acc;
				  }, [] )
				: [];
		}

		setLocations( newLocations );
	}, [ queryMode, JSON.stringify( list ), JSON.stringify( queriedListings ) ]);

	/**
	 * Create, update, or remove map when showMap attribute or locations change.
	 */
	useEffect(() => {
		// Don't run on the initial render.
		if ( initialRender ) {
			return;
		}

		// Don't bother if the Jetpack Maps block doesn't exist.
		if ( ! canUseMapBlock ) {
			return;
		}

		// If showMap toggle is enabled, update the existing map or create a new one.
		if ( showMap && 0 < locations.length ) {
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

				insertBlocks( [ newBlock ], 0, clientId );
			}
		} else if ( hasMap ) {
			// If disabling the showMap toggle, remove the existing map.
			removeBlocks( [ hasMap.clientId ] );
		}
	}, [ showMap, JSON.stringify( locations ) ]);

	/**
	 * Guard against accidentally deleting the list container block.
	 */
	useEffect(() => {
		if ( ! queryMode && ! list ) {
			// Create a new map at the bottom of the list.
			const newBlock = createBlock( 'newspack-listings/list-container' );

			insertBlocks( [ newBlock ], null, clientId );
		}
	}, [ JSON.stringify( innerBlocks ), queryMode ]);

	/**
	 * Render the results of the listing query.
	 *
	 * @param {Object} listing Post object for listing to show.
	 * @param {number} index Index of the item in the array.
	 */
	const renderQueriedListings = ( listing, index ) => {
		return (
			<div key={ index } className="newspack-listings__listing-editor newspack-listings__listing">
				<Listing attributes={ attributes } error={ error } post={ listing } />
				{
					<Button
						isLink
						href={ `/wp-admin/post.php?post=${ listing.id }&action=edit` }
						target="_blank"
					>
						{ __( 'Edit this listing', 'newspack-listing' ) }
					</Button>
				}
			</div>
		);
	};

	/**
	 * Validate location data.
	 *
	 * @param {*} location Location data to check.
	 * @return {boolean} True if the data is valid location data, false if not.
	 */
	const isValidLocation = location => {
		if (
			! location ||
			! location.id ||
			! location.coordinates ||
			! location.coordinates.latitude ||
			! location.coordinates.longitude
		) {
			return false;
		}

		return true;
	};

	/**
	 * Image size options for the sidebar.
	 */
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

	/**
	 * Show a hint to the user if there are no listings that can be added to the list.
	 */
	if ( isEmpty ) {
		return (
			<Placeholder icon={ <List /> } label={ __( 'Curated List', 'newspack-listings' ) }>
				<Notice isDismissible={ false }>
					{ __( 'Your site doesn’t have any listings. Create some to get started.' ) }
				</Notice>
			</Placeholder>
		);
	}

	/**
	 * Let user pick Query or Specific mode on startup.
	 */
	if ( startup ) {
		return (
			<div className="newspack-listings__placeholder">
				<BlockVariationPicker
					icon={ <List /> }
					label={ __( 'Curated List', 'newspack-listings' ) }
					instructions={ __( 'Select the type of list to start with.' ) }
					onSelect={ variation => {
						if ( variation.name && 'query' === variation.name ) {
							setAttributes( {
								queryMode: true,
								startup: false,
							} );
						} else {
							setAttributes( {
								startup: false,
							} );
						}
					} }
					variations={ [
						{
							name: 'query',
							title: __( 'Query', 'newspack-listings' ),
							icon: <Query />,
						},
						{
							name: 'specific',
							title: __( 'Specific Listings', 'newspack-listings' ),
							icon: <Specific />,
						},
					] }
				/>
			</div>
		);
	}

	return (
		<div className="newspack-listings__curated-list-editor">
			<InspectorControls>
				{ queryMode && (
					<PanelBody title={ __( 'Query Settings', 'newspack-listings' ) }>
						<SidebarQueryControls
							disabled={ isFetching }
							setAttributes={ setAttributes }
							queryOptions={ queryOptions }
							showLoadMore={ showLoadMore }
							loadMoreText={ loadMoreText }
						/>
					</PanelBody>
				) }
				<PanelBody title={ __( 'List Settings', 'newspack-listings' ) }>
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
				<PanelBody title={ __( 'Meta Settings', 'newspack-listings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Author', 'newspack-listings' ) }
							checked={ showAuthor }
							onChange={ () => setAttributes( { showAuthor: ! showAuthor } ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Category', 'newspack-listings' ) }
							checked={ showCategory }
							onChange={ () => setAttributes( { showCategory: ! showCategory } ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Tags', 'newspack-listings' ) }
							checked={ showTags }
							onChange={ () => setAttributes( { showTags: ! showTags } ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div
				className={ classes.join( ' ' ) }
				style={ {
					color: textColor || '#000',
				} }
			>
				{ queryMode && error && (
					<Notice className="newspack-listings__error" status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				<InnerBlocks
					allowedBlocks={ [ 'jetpack/map', 'newspack-listings/list-container' ] }
					template={ [ [ 'newspack-listings/list-container' ] ] } // Start with an empty list only.
					templateInsertUpdatesSelection={ false }
					renderAppender={ () => null } // We want to discourage editors from adding blocks in this top-level wrapper, but we can't lock the template because we still need to be able to programmatically add or remove map blocks.
				/>
				{ // If in query mode and while fetching posts.
				isFetching && queryMode && (
					<Placeholder>
						<Spinner />
					</Placeholder>
				) }
				{ // If in query mode, show the queried listings.
				! isFetching && queryMode && queriedListings.map( renderQueriedListings ) }
				{ ! isFetching &&
					queryMode &&
					showLoadMore &&
					queryOptions.maxItems <= queriedListings.length && (
						<Button className="newspack-listings__load-more" isPrimary>
							{ loadMoreText }
						</Button>
					) }
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
	const { insertBlocks, removeBlocks, updateBlockAttributes } = dispatch( 'core/block-editor' );

	return {
		insertBlocks,
		removeBlocks,
		updateBlockAttributes,
	};
};

export const CuratedListEditor = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( CuratedListEditorComponent );
