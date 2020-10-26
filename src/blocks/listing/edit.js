/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Spinner } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { Listing } from './listing';
import { QueryControls } from '../../components';

const ListingEditorComponent = ( {
	attributes,
	clientId,
	getBlock,
	getBlockParents,
	name,
	setAttributes,
} ) => {
	const [ post, setPost ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ isEditingPost, setIsEditingPost ] = useState( false );
	const { listing } = attributes;

	// Get the parent List Container block.
	const parents = getBlockParents( clientId );
	const parent = parents.reduce( ( acc, outerBlock ) => {
		const blockInfo = getBlock( outerBlock );

		if ( 'newspack-listings/list-container' === blockInfo.name ) {
			acc.listContainer = blockInfo;
		} else if ( 'newspack-listings/curated-list' === blockInfo.name ) {
			acc.curatedList = blockInfo;
		}

		return acc;
	}, {} );

	// Build an array of just the listing post IDs that exist in the parent Curated List block.
	const listItems = parent.listContainer.innerBlocks.reduce( ( acc, innerBlock ) => {
		if ( innerBlock.attributes.listing ) {
			acc.push( innerBlock.attributes.listing );
		}

		return acc;
	}, [] );

	const { post_types } = window.newspack_listings_data;
	const listingTypeSlug = name.split( '/' ).slice( -1 );
	const listingType = post_types[ listingTypeSlug ];

	// Fetch listing post data if we have a listing post ID.
	useEffect(() => {
		if ( listing ) {
			fetchPost( listing );
		}
	}, [ listing ]);

	// Sync parent attributes to listing attributes, so that we can use parent attributes in the PHP render callback.
	useEffect(() => {
		setAttributes( { ...parent.curatedList.attributes } );
	}, [ JSON.stringify( parent.curatedList.attributes ) ]);

	// Fetch listing post by listingId.
	const fetchPost = async listingId => {
		try {
			setError( null );
			const posts = await apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/listings', {
					per_page: 100,
					id: listingId,
					_fields: 'id,title,author,category,excerpt,media,meta',
				} ),
			} );

			if ( 0 === posts.length ) {
				throw `No posts found for ID ${ listingId }. Try refreshing or selecting a new post.`;
			}

			const foundPost = posts[ 0 ];

			if ( foundPost.meta && foundPost.meta.newspack_listings_locations ) {
				setAttributes( { locations: foundPost.meta.newspack_listings_locations } );
			}

			setPost( foundPost );
		} catch ( e ) {
			setError( e );
		}
	};

	// Renders the autocomplete search field to select listings. Will only show listings of the type that matches the block.
	const renderSearch = () => {
		return (
			<div className="newspack-listings__listing-search">
				<QueryControls
					label={ isEditingPost && __( 'Select a listing.', 'newspack-listings' ) }
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
			<Fragment>
				<Listing attributes={ parent.curatedList.attributes } error={ error } post={ post } />
				{ post && (
					<Button
						isLink
						href={ `/wp-admin/post.php?post=${ post.id }&action=edit` }
						target="_blank"
					>
						{ __( 'Edit this listing', 'newspack-listing' ) }
					</Button>
				) }
			</Fragment>
		);
	};

	return (
		<div className="newspack-listings__listing-editor newspack-listings__listing">
			{ ! listing || isEditingPost ? renderSearch() : renderPost() }
		</div>
	);
};

const mapStateToProps = select => {
	const { getBlock, getBlockParents } = select( 'core/block-editor' );

	return {
		getBlock,
		getBlockParents,
	};
};

export const ListingEditor = withSelect( mapStateToProps )( ListingEditorComponent );
