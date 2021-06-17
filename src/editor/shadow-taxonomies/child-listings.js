/**
 * UI for managing child listings and posts.
 */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	ExternalLink,
	Modal,
	Notice,
	PanelRow,
	ToggleControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * External dependencies
 */
import { AutocompleteWithSuggestions } from 'newspack-components';

/**
 * Internal dependencies
 */
import { getPostTypeLabel, getTaxonomyForPostType, isListing } from '../utils';
import './style.scss';

const ChildListingsComponent = ( { hideChildren, postId, updateMetaValue } ) => {
	const [ modalVisible, setModalVisible ] = useState( false );
	const [ isUpdating, setIsUpdating ] = useState( false );
	const [ initialChildPosts, setInitialChildPosts ] = useState( [] );
	const [ childPosts, setChildPosts ] = useState( [] );
	const [ selectedPostType, setSelectedPostType ] = useState( null );
	const [ message, setMessage ] = useState( null );
	const postType = window?.newspack_listings_data.post_type;
	const listingPostTypes = window?.newspack_listings_data.post_types || {};
	const taxonomyForPostType = getTaxonomyForPostType();
	const childPostTypes = taxonomyForPostType?.post_types || [];
	const filteredChildPostTypes = childPostTypes.filter(
		childPostType => childPostType !== postType
	);
	const canHaveChildren = 0 < filteredChildPostTypes.length && isListing();

	// Fetch suggestions for suggestions list on component mount.
	useEffect(() => {
		if ( canHaveChildren ) {
			setMessage( null );
			apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/children', {
					per_page: 100,
					post_id: postId,
				} ),
			} )
				.then( response => {
					if ( response ) {
						const mappedResponse = response.map( post => ( {
							value: post.value,
							label: post.label,
							postType: post.post_type,
						} ) );
						setChildPosts( mappedResponse );
						setInitialChildPosts( mappedResponse );
					}
				} )
				.catch( e => {
					setMessage( {
						status: 'error',
						children: e.message || __( 'Error fetching suggestions.', 'newspack-listings' ),
						isDismissible: false,
					} );
				} );
		}
	}, []);

	// Bail early if the post type can't have child listings.
	if ( ! canHaveChildren ) {
		return null;
	}

	// Get an array of post types that can be children of this post.
	const validPostTypes = [
		...Object.keys( listingPostTypes ).reduce( ( acc, listingType ) => {
			if (
				-1 < childPostTypes.indexOf( listingPostTypes[ listingType ].name ) &&
				listingPostTypes[ listingType ].name !== postType
			) {
				acc.push( {
					slug: listingPostTypes[ listingType ].name,
					label: listingPostTypes[ listingType ].label,
				} );
			}

			return acc;
		}, [] ),
		{ slug: 'post', label: 'Post' },
		{ slug: 'page', label: 'Page' },
	];

	// Update shadow terms for the children selected for this post.
	const update = async () => {
		setIsUpdating( true );
		setMessage( null );
		const addedPostIds = childPosts.reduce( ( acc, childPostToAdd ) => {
			if ( ! initialChildPosts.find( childPost => childPost.value === childPostToAdd.value ) ) {
				acc.push( childPostToAdd.value );
			}

			return acc;
		}, [] );
		const removedPostIds = initialChildPosts.reduce( ( acc, childPostToRemove ) => {
			if ( ! childPosts.find( childPost => childPost.value === childPostToRemove.value ) ) {
				acc.push( childPostToRemove.value );
			}

			return acc;
		}, [] );

		try {
			const response = await apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/children', {
					post_id: postId,
					added: addedPostIds,
					removed: removedPostIds,
				} ),
				method: 'POST',
			} );

			/**
			 * Because updating parent or child listings happens in real time instead of
			 * after clicking "Save" or "Update" on the current post, we should show a
			 * notice with the result of the operation in the editor to indicate whether
			 * the update happened successfully or not.
			 */
			if ( true === response ) {
				setInitialChildPosts( childPosts ); // Update saved state.
				setMessage( {
					status: 'success',
					children: __( 'Child listings updated.', 'newspack-listings' ),
					isDismissible: false,
				} );
			} else {
				setMessage( {
					status: 'error',
					children: __( 'Error updating child listings.', 'newspack-listings' ),
					isDismissible: false,
				} );
			}
		} catch ( e ) {
			setMessage( {
				status: 'error',
				children: e.message || __( 'Error updating child listings.', 'newspack-listings' ),
				isDismissible: false,
			} );
		}

		setIsUpdating( false );
	};

	const getLabel = () => {
		if ( selectedPostType ) {
			return sprintf(
				__( 'Search %ss', 'newspack-listings' ),
				getPostTypeLabel( selectedPostType )
			);
		}

		return __( 'Search', 'newspack-listings' );
	};

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__child-listings"
			name="newspack-listings-children"
			title={ __( 'Child Listings and Related Posts', 'newspack-listings' ) }
		>
			<PanelRow>
				<ToggleControl
					className={ 'newspack-listings__toggle-control' }
					label={ __( 'Hide child listings', 'newspack-listings' ) }
					help={ () => (
						<p>
							{ __( 'Overrides ', 'newspack-listings' ) }
							<ExternalLink href="/wp-admin/admin.php?page=newspack-listings-settings-admin">
								{ __( 'global settings', 'newspack-listings' ) }
							</ExternalLink>
						</p>
					) }
					checked={ hideChildren }
					onChange={ value => updateMetaValue( 'newspack_listings_hide_children', value ) }
				/>
			</PanelRow>
			{ ! hideChildren && (
				<Button isSecondary onClick={ () => setModalVisible( true ) }>
					{ __( 'Manage Child Listings' ) }
				</Button>
			) }
			{ modalVisible && (
				<Modal
					className="newspack-listings__modal"
					title={ __( 'Manage Child Listings and Related Posts' ) }
					onRequestClose={ () => {
						setModalVisible( false );
						setMessage( null );
					} }
				>
					{ message && <Notice { ...message } /> }
					{ childPosts && 0 === childPosts.length && (
						<p className="newspack-listings__empty-message">
							{ __( 'No items selected.', 'newspack-listings' ) }
						</p>
					) }
					<AutocompleteWithSuggestions
						hideHelp
						multiSelect
						label={ getLabel() }
						help={ __(
							'Begin typing post title, click autocomplete result to select.',
							'newspack'
						) }
						onChange={ items => {
							setMessage( null );
							setChildPosts( items );
						} }
						onPostTypeChange={ _postType => setSelectedPostType( _postType ) }
						postTypes={ validPostTypes }
						selectedItems={ childPosts }
					/>
					<div className="newspack-listings__modal-actions">
						<Button
							isSecondary
							onClick={ () => {
								setChildPosts( initialChildPosts ); // Reset state to last saved state.
								setModalVisible( false );
							} }
						>
							{ __( 'Cancel', 'newspack-listings' ) }
						</Button>
						<Button
							isPrimary
							disabled={
								isUpdating || JSON.stringify( childPosts ) === JSON.stringify( initialChildPosts )
							}
							onClick={ update }
						>
							<>
								{ isUpdating
									? __( 'Saving...', 'newspack-listings' )
									: __( 'Save', 'newspack-listings' ) }
							</>
						</Button>
					</div>
				</Modal>
			) }
		</PluginDocumentSettingPanel>
	);
};

const mapStateToProps = select => {
	const { getCurrentPostId, getEditedPostAttribute } = select( 'core/editor' );
	const meta = getEditedPostAttribute( 'meta' );

	return {
		getEditedPostAttribute,
		hideChildren: meta.newspack_listings_hide_children,
		postId: getCurrentPostId(),
	};
};

const mapDispatchToProps = dispatch => {
	const { editPost } = dispatch( 'core/editor' );
	const { createNotice } = dispatch( 'core/notices' );

	return {
		createNotice,
		editPost,
		updateMetaValue: ( key, value ) => editPost( { meta: { [ key ]: value } } ),
	};
};

export const ChildListings = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( ChildListingsComponent );
