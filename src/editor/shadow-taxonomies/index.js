/**
 * Filtered taxonomy UI for Listings shadow taxonomies.
 * Replaces the default "add new" buttons with a link to create a new post of the corresponding type.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { ExternalLink, PanelRow, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { ParentListings } from './parent-listings';
import { TaxonomySearch } from './taxonomy-search';
import { getPostTypeByTaxonomy, getTaxonomyForPostType } from '../utils';
import './style.scss';

const ShadowTaxonomiesComponent = ( {
	createNotice,
	getEditedPostAttribute,
	meta,
	postId,
	updateMetaValue,
} ) => {
	const { post_type: postType, taxonomies } = window?.newspack_listings_data;
	const { newspack_listings_hide_children } = meta;
	const taxonomyForPostType = getTaxonomyForPostType();
	const childPostTypes = taxonomyForPostType ? taxonomyForPostType.post_types : [];
	const filteredChildPostTypes = childPostTypes.filter(
		childPostType => childPostType !== postType
	);
	const canHaveChildren = 0 < filteredChildPostTypes.length;
	let canHaveParents = false;

	for ( const taxonomy in taxonomies ) {
		const taxonomyPostType = getPostTypeByTaxonomy( taxonomies[ taxonomy ].name );
		const postTypeIsChild = -1 < taxonomies[ taxonomy ].post_types.indexOf( postType );

		// Posts, pages, and listings can have parents if they can be assigned listing shadow terms.
		if ( postTypeIsChild && taxonomyPostType !== postType ) {
			canHaveParents = true;
		}
	}

	// Bail early if the post type can't have parent or child listings.
	if ( ! canHaveParents && ! canHaveChildren ) {
		return null;
	}

	const postStatus = getEditedPostAttribute( 'status' );

	return (
		<>
			{ canHaveParents && <ParentListings /> }
			{ canHaveChildren && (
				<PluginDocumentSettingPanel
					className="newspack-listings__shadow-taxonomies-sidebar"
					name="newspack-listings-children"
					title={ __( 'Child Listings', 'newspack-listings' ) }
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
							checked={ newspack_listings_hide_children }
							onChange={ value => updateMetaValue( 'newspack_listings_hide_children', value ) }
						/>
					</PanelRow>

					{ 'publish' !== postStatus && (
						<p>{ __( 'Publish this listing to assign children.', 'newspack-listings' ) }</p>
					) }

					{ 'publish' === postStatus &&
						! newspack_listings_hide_children &&
						filteredChildPostTypes.map( childPostType => {
							return (
								<TaxonomySearch
									key={ childPostType }
									fetchSaved={ async ( ids, type ) => {
										const [ id ] = ids;
										return await apiFetch( {
											path: addQueryArgs( '/newspack-listings/v1/children', {
												per_page: 100,
												post_id: id,
												post_type: type,
											} ),
										} );
									} }
									fetchSuggestions={ async ( search, type ) => {
										if ( ! search ) {
											return;
										}

										// Standard post and page endpoints are plural.
										const endpoint = 'post' === type || 'page' === type ? type + 's' : type;
										const response = await apiFetch( {
											path: addQueryArgs( `/wp/v2/${ endpoint }`, {
												search,
												per_page: 20,
												_fields: 'id,title',
												orderby: 'title',
												order: 'asc',
												status: 'publish,draft,pending,future',
											} ),
										} );

										return response.map( item => ( {
											id: item.id,
											name: item.title?.rendered || __( '(no title)', 'newspack-listings' ),
										} ) );
									} }
									postType={ childPostType }
									savedIds={ [ postId ] }
									update={ async ( postIds, removedPostIds ) => {
										const response = await apiFetch( {
											path: addQueryArgs( '/newspack-listings/v1/children', {
												post_id: postId,
												added: postIds,
												removed: removedPostIds,
											} ),
											method: 'POST',
										} );

										/**
										 * Because (unlike adding terms to the current post) updating child posts happens
										 * in real time instead of after clicking "Save" or "Update" on the current post,
										 * we should show a notice with the result of the operation in the editor to indicate.
										 * that the update happened successfully or not.
										 */
										if ( true === response ) {
											createNotice(
												'success',
												__( 'Child listings updated.', 'newspack-listings' ),
												{
													id: 'newspack-listings__child-update-success',
													isDismissible: true,
													type: 'default',
												}
											);
										} else {
											createNotice(
												'error',
												__( 'Error updating child listings.', 'newspack-listings' ),
												{
													id: 'newspack-listings__child-update-error',
													isDismissible: true,
													type: 'default',
												}
											);
										}
									} }
								/>
							);
						} ) }
				</PluginDocumentSettingPanel>
			) }
		</>
	);
};

const mapStateToProps = select => {
	const { getCurrentPostId, getEditedPostAttribute } = select( 'core/editor' );

	return {
		getEditedPostAttribute,
		meta: getEditedPostAttribute( 'meta' ),
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

export const ShadowTaxonomies = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( ShadowTaxonomiesComponent );
