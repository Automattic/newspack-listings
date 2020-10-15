/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { createBlock } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	ButtonGroup,
	Modal,
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
import { SidebarQueryControls } from '../../components/';
import { getCuratedListClasses, getKey } from '../../editor/utils';

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
	const [ showModal, setShowModal ] = useState( false );
	const [ hasMorePages, setHasMorePages ] = useState( false );
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
		queryMode,
		queryOptions,
		queriedListings,
		showLoadMore,
		loadMoreText,
	} = attributes;

	const list = innerBlocks.find(
		innerBlock => innerBlock.name === 'newspack-listings/list-container'
	);
	const listingBlocks = list ? list.innerBlocks.map( innerBlock => innerBlock.clientId ) : [];
	const hasMap = innerBlocks.find( innerBlock => innerBlock.name === 'jetpack/map' );
	const classes = getCuratedListClasses( className, attributes );
	const { post_types } = window.newspack_listings_data;

	// If changing query options, fetch listing posts that match the query.
	useEffect(() => {
		fetchPosts( queryOptions );
	}, [ JSON.stringify( queryOptions ) ]);

	// Update locations in component state. This lets us keep the map block in sync with listing items.
	useEffect(() => {
		if ( ! canUseMapBlock ) {
			return;
		}

		let newLocations = [];

		// Only build locations array if we have any listings, and the Jetpack Maps block exists.
		if ( queryMode ) {
			newLocations = queriedListings.reduce( ( acc, queriedListing ) => {
				if ( queriedListing.meta && queriedListing.meta.newspack_listings_locations ) {
					queriedListing.meta.newspack_listings_locations.map( location => acc.push( location ) );
				}

				return acc;
			}, [] );
		} else {
			newLocations = list
				? list.innerBlocks.reduce( ( acc, innerBlock ) => {
						if ( innerBlock.attributes.locations && 0 < innerBlock.attributes.locations.length ) {
							innerBlock.attributes.locations.map( location => acc.push( location ) );
						}
						return acc;
				  }, [] )
				: [];
		}

		setLocations( newLocations );
	}, [ queryMode, JSON.stringify( list ), JSON.stringify( queriedListings ) ]);

	// Create, update, or remove map when showMap attribute or locations change.
	useEffect(() => {
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

	// If we have listings that might be lost, warn the user. Otherwise, just swtich modes.
	const maybeShowModal = () => {
		if (
			( ! queryMode && 0 === listingBlocks.length ) ||
			( queryMode && 0 === queriedListings.length )
		) {
			setAttributes( { queryMode: ! queryMode } );
			return;
		}

		setShowModal( true );
	};

	// Use current query options to get listing posts.
	const fetchPosts = async query => {
		setIsFetching( true );

		// Remove blocks from prior queries.
		if ( 0 < listingBlocks.length ) {
			removeBlocks( listingBlocks );
		}

		try {
			setError( null );
			const response = await apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/listings', {
					query,
					_fields: 'id,title,author,category,excerpt,media,meta,type',
				} ),
				parse: false,
			} );

			const nextUrl = response.headers.get( 'next-url' );
			const posts = await response.json();

			setAttributes( { queriedListings: posts } );

			if ( nextUrl && showLoadMore ) {
				setHasMorePages( true );
			} else {
				setHasMorePages( false );
			}

			if ( 0 === posts.length ) {
				throw 'No posts matching query options. Try selecting different or less specific query options.';
			}
		} catch ( e ) {
			setError( e );
		}

		setIsFetching( false );
	};

	// Render the results of the listing query.
	const renderQueriedListings = ( listing, index ) => {
		return (
			<div className="newspack-listings__listing-editor newspack-listings__listing">
				<span className="newspack-listings__listing-label">
					{ getKey( post_types, listing.type ) }
				</span>
				<Listing key={ index } attributes={ attributes } error={ error } post={ listing } />
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
				<PanelBody title={ __( 'Query Settings', 'newspack-listings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Query mode', 'newspack-listings' ) }
							checked={ queryMode }
							onChange={ () => maybeShowModal() }
						/>
					</PanelRow>
					{ queryMode && (
						<SidebarQueryControls
							disabled={ isFetching }
							setAttributes={ setAttributes }
							queryOptions={ queryOptions }
							showLoadMore={ showLoadMore }
							loadMoreText={ loadMoreText }
						/>
					) }
				</PanelBody>
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
				</PanelBody>
			</InspectorControls>
			<div className={ classes.join( ' ' ) }>
				<span className="newspack-listings__curated-list-label">
					{ __( 'Curated List', 'newspack-listings' ) }
				</span>
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
				{ ! isFetching && queryMode && hasMorePages && (
					<Button className="newspack-listings__load-more" isPrimary>
						{ loadMoreText }
					</Button>
				) }
			</div>
			{ showModal && (
				<Modal
					className="newspack-listings__modal"
					title={ __( 'Change query mode?' ) }
					onRequestClose={ () => setShowModal( false ) }
				>
					<p>
						{ __(
							'Are you sure you want to change the query mode? Doing so will delete the existing items in this list.',
							'newspack-listings'
						) }
					</p>
					<Button
						isPrimary
						onClick={ () => {
							// Confirm: delete all listing item and map blocks, if applicable.
							if ( hasMap ) {
								listingBlocks.push( hasMap.clientId );
							}
							removeBlocks( listingBlocks );
							setAttributes( { queryMode: ! queryMode } );
							setShowModal( false );
						} }
					>
						{ __( 'OK', 'newspack-listings' ) }
					</Button>
					<Button
						isSecondary
						onClick={ () => {
							// Cancel: reset query mode without deleting inner listing item or map blocks.
							setShowModal( false );
						} }
					>
						{ __( 'Cancel', 'newspack-listings' ) }
					</Button>
				</Modal>
			) }
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
