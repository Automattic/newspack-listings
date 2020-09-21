/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { withSelect } from '@wordpress/data';
import { Fragment, RawHTML, useEffect, useState } from '@wordpress/element';
import { InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import {
	Button,
	ButtonGroup,
	Notice,
	PanelBody,
	PanelRow,
	RangeControl,
	ToggleControl,
	Spinner,
	BaseControl,
} from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { QueryControls } from '../../components';

const ListingEditorComponent = ( { attributes, listItems, meta, name, setAttributes } ) => {
	const [ post, setPost ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ isEditingPost, setIsEditingPost ] = useState( false );
	const {
		listing,
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

	const { newspack_listings_show_map, newspack_listings_show_numbers } = meta;
	const { post_types } = window.newspack_listings_data;
	const classes = [ 'newspack-listings__list-item' ];

	const listingTypeSlug = name.split( '/' ).slice( -1 );
	const listingType = post_types[ listingTypeSlug ];

	classes.push( listingTypeSlug );

	if ( newspack_listings_show_map ) classes.push( 'newspack-listings__show-map' );

	if ( newspack_listings_show_numbers ) classes.push( 'newspack-listings__show-numbers' );

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

	// Fetch listing post data if we have a listing post ID.
	useEffect(() => {
		if ( listing ) {
			fetchPost( listing );
		}
	}, [ listing ]);

	// Fetch listing post title and content by listingId.
	const fetchPost = async listingId => {
		try {
			setError( null );
			const posts = await apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/listings', {
					per_page: 100,
					id: listingId,
					_fields: 'id,title,content,meta',
				} ),
			} );

			if ( 0 === posts.length ) {
				throw `No posts found for ID ${ listingId }. Try refreshing or selecting a new post.`;
			}

			setPost( posts[ 0 ] );
		} catch ( e ) {
			setError( e );
		}
	};

	// Renders the autocomplete search field to select listings. Will only show listings of the type that matches the block.
	const renderSearch = () => {
		return (
			<div className="newspack-listings__list-item-post">
				<QueryControls
					label={
						isEditingPost && post && post.title
							? __( 'Select a new listing to replace “', 'newspack-listings' ) + post.title + '”'
							: __( 'Select a listing.', 'newspack-listings' )
					}
					listingType={ listingType }
					maxLength={ 1 }
					onChange={ _listing => {
						if ( _listing.length ) {
							setIsEditingPost( false );
							setPost( null );
							setAttributes( { listing: _listing[ 0 ] } );
						}
					} }
					selectedPost={ isEditingPost ? null : listing }
					listItems={ listItems }
				/>
				{ listing && (
					<Button isPrimary onClick={ () => setIsEditingPost( false ) }>
						{ __( 'Cancel', 'newspack-listings' ) }
					</Button>
				) }

				<Button
					isSecondary
					href={ `/wp-admin/post-new.php?post_type=${ listingType }` }
					target="_blank"
				>
					{ __( 'Create new listing', 'newspack-listing' ) }
				</Button>
			</div>
		);
	};

	// Renders selected listing post, or a placeholder if still fetching.
	const renderPost = () => {
		if ( ! post && ! error ) {
			return <Spinner />;
		}

		return (
			<div className="newspack-listings__list-item-post">
				{ error && (
					<Notice className="newspack-listings__error" status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ post && post.title && (
					<h3 className="newspack-listings__list-item-title">
						<RawHTML>{ post.title }</RawHTML>
					</h3>
				) }
				{ post && post.content && <RawHTML>{ post.content }</RawHTML> }
				<Button isSecondary onClick={ () => setIsEditingPost( true ) }>
					{ __( 'Replace listing', 'newspack-listing' ) }
				</Button>
				{ post && (
					<Button
						isLink
						href={ `/wp-admin/post.php?post=${ listing }&action=edit` }
						target="_blank"
					>
						{ __( 'Edit this listing', 'newspack-listing' ) }
					</Button>
				) }
			</div>
		);
	};

	return (
		<div className="newspack-listings__list-item-editor">
			<InspectorControls>
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
				<span className="newspack-listings__list-item-label">{ listingTypeSlug }</span>
				{ ! listing || isEditingPost ? renderSearch() : renderPost() }
			</div>
		</div>
	);
};

const mapStateToProps = select => {
	const { getBlocks, getEditedPostAttribute } = select( 'core/editor' );
	const blocks = getBlocks();

	// Build an array of just the list item post IDs that exist in the parent post.
	const listItems = blocks.reduce( ( acc, item ) => {
		if ( item.innerBlocks && 0 < item.innerBlocks.length ) {
			item.innerBlocks.forEach( innerBlock => {
				if ( innerBlock.attributes.listing ) {
					acc.push( innerBlock.attributes.listing );
				}
			} );
		}
		return acc;
	}, [] );

	return {
		meta: getEditedPostAttribute( 'meta' ),
		listItems,
	};
};

export const ListingEditor = withSelect( mapStateToProps )( ListingEditorComponent );
