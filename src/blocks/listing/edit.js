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
	PanelBody,
	PanelRow,
	Placeholder,
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

const CuratedListEditorComponent = ( { attributes, listItems, meta, setAttributes } ) => {
	const [ post, setPost ] = useState( null );
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

	const listingType = meta.newspack_listings_type || 'newspack_lst_generic';

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

	useEffect(() => {
		if ( listing ) {
			fetchPost( listing );
		}
	}, [ listing ]);

	// Fetch listing title and content by listingId.
	const fetchPost = async listingId => {
		try {
			const posts = await apiFetch( {
				path: addQueryArgs( '/wp/v2/' + ( listingType || 'newspack_lst_generic' ), {
					per_page: 100,
					include: listingId,
					_fields: 'id,title,content',
				} ),
			} );

			if ( 0 === posts.length ) {
				throw 'No posts found for ID ' + listingId;
			}

			setPost( posts[ 0 ] );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( e );
		}
	};

	// Renders the autocomplete search field to select listings. Will only show listings of the type selected in the parent post's metadata.
	const renderSearch = () => {
		return (
			<div className="newspack-listings__list-item-post">
				<QueryControls
					label={
						isEditingPost && post.title.rendered
							? __( 'Select a new listing to replace “', 'newspack-listings' ) + post.title.rendered
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
			</div>
		);
	};

	// Renders selected listing post, or a placeholder if still fetching.
	const renderPost = () => {
		if ( ! post ) {
			return (
				<Placeholder>
					<Spinner />
				</Placeholder>
			);
		}

		return (
			<div className="newspack-listings__list-item-post">
				<h3 className="newspack-listings__list-item-title">{ post.title.rendered }</h3>
				<RawHTML>{ post.content.rendered }</RawHTML>
				<Button isSecondary onClick={ () => setIsEditingPost( true ) }>
					{ __( 'Replace listing', 'newspack-listing' ) }
				</Button>
				<Button isLink href={ `/wp-admin/post.php?post=${ listing }&action=edit` } target="_blank">
					{ __( 'Edit this listing', 'newspack-listing' ) }
				</Button>
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

			<div className="newspack-listings__list-item">
				{ ! listing || isEditingPost ? renderSearch() : renderPost() }
			</div>
		</div>
	);
};

const mapStateToProps = select => {
	const { getBlocks, getEditedPostAttribute } = select( 'core/editor' );
	const blocks = getBlocks();

	// Build an array of just the list item post IDs.
	const listItems = blocks.reduce( ( acc, item ) => {
		if ( item.attributes.listing ) {
			acc.push( item.attributes.listing );
		}
		return acc;
	}, [] );

	return {
		meta: getEditedPostAttribute( 'meta' ),
		listItems,
	};
};

export const CuratedListEditor = withSelect( mapStateToProps )( CuratedListEditorComponent );
