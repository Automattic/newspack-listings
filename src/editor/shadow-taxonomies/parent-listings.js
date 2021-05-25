/**
 * UI for managing parent listings.
 */

/**
 * WordPress dependencies
 */
import { __, sprintf, _x } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	ExternalLink,
	Modal,
	Notice,
	PanelRow,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useEffect, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { addQueryArgs } from '@wordpress/url';

/**
 * External dependencies
 */
import { AutocompleteWithSuggestions } from 'newspack-components';

/**
 * Internal dependencies
 */
import { isListing } from '../utils';
import './style.scss';

const ParentListingsComponent = ( { hideParents, postId, updateMetaValue } ) => {
	const [ isUpdating, setIsUpdating ] = useState( false );
	const [ modalVisible, setModalVisible ] = useState( false );
	const [ initialParentTerms, setInitialParentTerms ] = useState( null );
	const [ parentTerms, setParentTerms ] = useState( null );
	const [ message, setMessage ] = useState( null );
	const postType = window?.newspack_listings_data.post_type;
	const postTypes = window?.newspack_listings_data.post_types || {};
	const taxonomies = window?.newspack_listings_data.taxonomies || {};
	const validTaxonomies = Object.keys( taxonomies ).reduce( ( acc, type ) => {
		const taxonomy = taxonomies[ type ];
		if ( -1 < taxonomy.post_types.indexOf( postType ) && postTypes[ type ].name !== postType ) {
			acc.push( { slug: taxonomy.name, label: taxonomy.label } );
		}
		return acc;
	}, [] );

	useEffect(() => {
		apiFetch( {
			path: addQueryArgs( '/newspack-listings/v1/parents', {
				per_page: 100,
				post_id: postId,
			} ),
		} ).then( response => {
			const mappedResponse = response.map( post => ( {
				value: post.value,
				label: post.label,
				postType: post.post_type,
			} ) );
			setParentTerms( mappedResponse );
			setInitialParentTerms( mappedResponse );
		} );
	}, []);

	// For now, only show parent listings UI with posts and pages, not listings.
	if ( isListing() ) {
		return null;
	}

	const update = async () => {
		setIsUpdating( true );
		setMessage( null );
		const addedTerms = parentTerms.reduce( ( acc, termToAdd ) => {
			if ( ! initialParentTerms.find( term => term.value === termToAdd.value ) ) {
				acc.push( { id: termToAdd.value, taxonomy: termToAdd.postType } );
			}

			return acc;
		}, [] );
		const removedTerms = initialParentTerms.reduce( ( acc, termToRemove ) => {
			if ( ! parentTerms.find( term => term.value === termToRemove.value ) ) {
				acc.push( { id: termToRemove.value, taxonomy: termToRemove.postType } );
			}

			return acc;
		}, [] );

		try {
			const response = await apiFetch( {
				path: addQueryArgs( '/newspack-listings/v1/parents', {
					post_id: postId,
					added: addedTerms,
					removed: removedTerms,
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
				setInitialParentTerms( parentTerms ); // Update saved state.
				setMessage( {
					status: 'success',
					children: sprintf(
						__( '%s listing%s updated.', 'newspack-listings' ),
						isListing()
							? __( 'Parent', 'newspack-listings' )
							: __( 'Related', 'newspack-listings' ),
						isListing() ? '' : _x( 's', 'pluralization', 'newspack-listings' )
					),
					isDismissible: false,
				} );
			} else {
				setMessage( {
					status: 'error',
					children: sprintf(
						__( 'Error updating %s listing%s.', 'newspack-listings' ),
						isListing()
							? __( 'parent', 'newspack-listings' )
							: __( 'related', 'newspack-listings' ),
						isListing() ? '' : _x( 's', 'pluralization', 'newspack-listings' )
					),
					isDismissible: false,
				} );
			}
		} catch ( e ) {
			setMessage( {
				status: 'error',
				children:
					e.message ||
					sprintf(
						__( 'Error updating %s listing%s.', 'newspack-listings' ),
						isListing()
							? __( 'parent', 'newspack-listings' )
							: __( 'related', 'newspack-listings' ),
						isListing() ? '' : _x( 's', 'pluralization', 'newspack-listings' )
					),
				isDismissible: false,
			} );
		}

		setIsUpdating( false );
	};

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__parent-listings"
			name="newspack-listings-parents"
			title={ sprintf(
				'%s Listing%s',
				isListing()
					? __( 'Parent', 'newspack-listings' )
					: __( 'Related Newspack', 'newspack-listings' ),
				isListing() ? '' : _x( 's', 'pluralization', 'newspack-listings' )
			) }
		>
			<PanelRow>
				<ToggleControl
					className={ 'newspack-listings__toggle-control' }
					label={ sprintf(
						__( 'Hide %s listing%s', 'newspack-listings' ),
						isListing()
							? __( 'parent', 'newspack-listings' )
							: __( 'related', 'newspack-listings' ),
						isListing() ? '' : _x( 's', 'pluralization', 'newspack-listings' )
					) }
					help={ () => (
						<p>
							{ __( 'Overrides ', 'newspack-listings' ) }
							<ExternalLink href="/wp-admin/admin.php?page=newspack-listings-settings-admin">
								{ __( 'global settings', 'newspack-listings' ) }
							</ExternalLink>
						</p>
					) }
					checked={ hideParents }
					onChange={ value => updateMetaValue( 'newspack_listings_hide_parents', value ) }
				/>
			</PanelRow>
			{ ! hideParents && (
				<Button isSecondary onClick={ () => setModalVisible( true ) }>
					{ sprintf(
						__( 'Manage %s Listing%s' ),
						isListing()
							? __( 'Parent', 'newspack-listings' )
							: __( 'Related', 'newspack-listings' ),
						isListing() ? '' : _x( 's', 'pluralization', 'newspack-listings' )
					) }
				</Button>
			) }
			{ modalVisible && (
				<Modal
					className="newspack-listings__modal"
					title={ sprintf(
						__( 'Manage %s Listing%s' ),
						isListing()
							? __( 'Parent', 'newspack-listings' )
							: __( 'Related', 'newspack-listings' ),
						isListing() ? '' : _x( 's', 'pluralization', 'newspack-listings' )
					) }
					onRequestClose={ () => {
						setModalVisible( false );
						setMessage( null );
					} }
				>
					{ message && <Notice { ...message } /> }
					{ parentTerms && 0 === parentTerms.length && (
						<p className="newspack-listings__empty-message">
							{ __( 'No listings selected.', 'newspack-listings' ) }
						</p>
					) }
					{ null === parentTerms && <Spinner /> }

					<AutocompleteWithSuggestions
						hideHelp
						multiSelect={ ! isListing() } // Listings can only have one parent, but posts and pages can have many.
						label={ __( 'Search listings', 'newspack' ) }
						help={ __(
							'Begin typing listing title, click autocomplete result to select.',
							'newspack'
						) }
						fetchSuggestions={ async ( search = null, offset = 0, taxonomyToSearch = null ) => {
							if ( taxonomyToSearch ) {
								const terms = await apiFetch( {
									path: addQueryArgs( '/wp/v2/' + taxonomyToSearch, {
										search,
										offset,
										per_page: 100,
									} ),
								} );

								return terms.map( term => ( {
									value: term.id,
									label: decodeEntities( term.name ) || __( '(no title)', 'newspack' ),
									postType: term.taxonomy,
								} ) );
							}
						} }
						onChange={ items => {
							setMessage( null );
							setParentTerms( items );
						} }
						postTypes={ validTaxonomies }
						postTypeLabel={ 'listing' }
						postTypeLabelPlural={ 'listings' }
						selectedItems={ parentTerms }
					/>
					<div className="newspack-listings__modal-actions">
						<Button
							isSecondary
							onClick={ () => {
								setParentTerms( initialParentTerms ); // Reset state to last saved state.
								setModalVisible( false );
							} }
						>
							{ __( 'Cancel', 'newspack-listings' ) }
						</Button>
						<Button
							isPrimary
							disabled={
								isUpdating || JSON.stringify( parentTerms ) === JSON.stringify( initialParentTerms )
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
		hideParents: meta.newspack_listings_hide_parents,
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

export const ParentListings = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( ParentListingsComponent );
