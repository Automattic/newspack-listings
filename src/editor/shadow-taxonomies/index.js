/**
 * Filtered taxonomy UI for Listings shadow taxonomies.
 * Replaces the default "add new" buttons with a link to create a new post of the corresponding type.
 */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { ExternalLink, PanelRow, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { TaxonomySearch } from './taxonomy-search';
import { getPostTypeByTaxonomy, getTaxonomyForPostType, isListing } from '../utils';
import './style.scss';

export const ShadowTaxonomiesComponent = ( {
	createNotice,
	editPost,
	getEditedPostAttribute,
	meta,
	postId,
	updateMetaValue,
} ) => {
	const { post_type: postType, taxonomies } = window?.newspack_listings_data;
	const { newspack_listings_hide_children, newspack_listings_hide_parents } = meta;
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

	return (
		<>
			{ canHaveParents && (
				<PluginDocumentSettingPanel
					className="newspack-listings__shadow-taxonomies-sidebar"
					name="newspack-listings-parents"
					title={
						isListing()
							? __( 'Parent Listings', 'newspack-listings' )
							: __( 'Related Newspack Listings', 'newspack-listings' )
					}
				>
					<PanelRow>
						<ToggleControl
							className={ 'newspack-listings__toggle-control' }
							label={ sprintf(
								__( 'Hide %s listings', 'newspack-listings' ),
								isListing()
									? __( 'parent', 'newspack-listings' )
									: __( 'related', 'newspack-listings' )
							) }
							help={ () => (
								<p>
									{ __( 'Overrides ', 'newspack-listings' ) }
									<ExternalLink href="/wp-admin/admin.php?page=newspack-listings-settings-admin">
										{ __( 'global settings', 'newspack-listings' ) }
									</ExternalLink>
								</p>
							) }
							checked={ newspack_listings_hide_parents }
							onChange={ value => updateMetaValue( 'newspack_listings_hide_parents', value ) }
						/>
					</PanelRow>

					{ ! newspack_listings_hide_parents &&
						Object.keys( taxonomies ).map( slug => {
							const { name, post_types: childPostTypesForTaxonomy } = taxonomies[ slug ];
							const taxonomyPostType = getPostTypeByTaxonomy( name );

							/**
							 * Only show shadow terms for listing types that can own this post type.
							 * Don't show shadow terms for the same type of listing as the current post.
							 */
							if (
								-1 === childPostTypesForTaxonomy.indexOf( postType ) ||
								taxonomyPostType === postType
							) {
								return null;
							}

							const terms = getEditedPostAttribute( name );
							return (
								<TaxonomySearch
									key={ slug }
									postId={ postId }
									postTitle={ getEditedPostAttribute( 'title' ) }
									savedIds={ terms }
									fetchSaved={ async ( ids, tax ) => {
										return await apiFetch( {
											path: addQueryArgs( '/newspack-listings/v1/terms', {
												per_page: 100,
												_fields: 'id,name',
												taxonomy: tax,
												include: ids.join( ',' ),
											} ),
										} );
									} }
									fetchSuggestions={ async ( search, tax ) => {
										return await apiFetch( {
											path: addQueryArgs( '/newspack-listings/v1/terms', {
												search,
												per_page: 20,
												_fields: 'id,name',
												orderby: 'count',
												order: 'desc',
												taxonomy: tax,
											} ),
										} );
									} }
									taxonomy={ taxonomies[ slug ] }
									update={ termIds => {
										const newTerms = {};
										newTerms[ name ] = termIds;
										editPost( newTerms );
									} }
								/>
							);
						} ) }
				</PluginDocumentSettingPanel>
			) }
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

					{ ! newspack_listings_hide_children &&
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
										const response = await apiFetch( {
											path: addQueryArgs( `/wp/v2/${ type }`, {
												search,
												per_page: 20,
												_fields: 'id,title',
												orderby: 'title',
												order: 'asc',
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
												children: postIds,
												parent: postId,
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
