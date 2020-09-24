/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { withSelect } from '@wordpress/data';
import { RawHTML, useEffect, useState } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { QueryControls } from '../../components';

const ListingEditorComponent = ( {
	attributes,
	className,
	clientId,
	getBlock,
	getBlockRootClientId,
	name,
	setAttributes,
} ) => {
	const [ post, setPost ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ isEditingPost, setIsEditingPost ] = useState( false );
	const { listing } = attributes;

	// Get the parent Curated List block.
	const parent = getBlock( getBlockRootClientId( clientId ) );

	// Parent Curated List block attributes.
	const {
		showExcerpt,
		showImage,
		showCaption,
		// minHeight,
		// showCategory,
		// mediaPosition,
		// typeScale,
		// imageScale,
		// mobileStack,
		// textColor,
		// showSubtitle,
	} = parent.attributes;

	// Build an array of just the listing post IDs that exist in the parent Curated List block.
	const listItems = parent.innerBlocks.reduce( ( acc, innerBlock ) => {
		if ( innerBlock.attributes.listing ) {
			acc.push( innerBlock.attributes.listing );
		}

		return acc;
	}, [] );

	const { post_types } = window.newspack_listings_data;
	const classes = [ className, 'newspack-listings__listing' ];
	const listingTypeSlug = name.split( '/' ).slice( -1 );
	const listingType = post_types[ listingTypeSlug ];

	classes.push( listingTypeSlug );

	// Fetch listing post data if we have a listing post ID.
	useEffect(() => {
		if ( listing ) {
			fetchPost( listing );
		}
	}, [ listing ]);

	// Sync parent attributes to listing attributes, so that we can use parent attributes in the PHP render callback.
	useEffect(() => {
		setAttributes( { ...parent.attributes } );
	}, [ JSON.stringify( parent.attributes ) ]);

	// Fetch listing post by listingId.
	const fetchPost = async listingId => {
		try {
			setError( null );
			const posts = await apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/listings', {
					per_page: 100,
					id: listingId,
					_fields: 'id,title,excerpt,media,meta',
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
			<div className="newspack-listings__listing-post">
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
			<div className="newspack-listings__listing-post">
				{ error && (
					<Notice className="newspack-listings__error" status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
				{ post && post.title && (
					<h3 className="newspack-listings__listing-title">
						<RawHTML>{ post.title }</RawHTML>
					</h3>
				) }
				{ showImage && post && post.media && post.media.image && (
					<figure className="newspack-listings__listing-featured-media">
						<img
							className="newspack-listings__listing-thumbnail"
							src={ post.media.image }
							alt={ post.media.caption || post.title }
						/>
						{ showCaption && post.media.caption && (
							<figcaption className="newspack-listings__listing-caption">
								{ post.media.caption }
							</figcaption>
						) }
					</figure>
				) }
				{ showExcerpt && post && post.excerpt && <RawHTML>{ post.excerpt }</RawHTML> }
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
		<div className="newspack-listings__listing-editor">
			<div className={ classes.join( ' ' ) }>
				<span className="newspack-listings__listing-label">{ listingTypeSlug }</span>
				{ ! listing || isEditingPost ? renderSearch() : renderPost() }
			</div>
		</div>
	);
};

const mapStateToProps = select => {
	const { getBlock, getBlockRootClientId } = select( 'core/block-editor' );

	return {
		getBlock,
		getBlockRootClientId,
	};
};

export const ListingEditor = withSelect( mapStateToProps )( ListingEditorComponent );
