/**
 * UI for managing parent listings.
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
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * External dependencies
 */
import groupBy from 'lodash/groupBy';

/**
 * Internal dependencies
 */
import { isListing } from '../utils';
import './style.scss';

const ParentListingsComponent = ( { hideParents, postId, updateMetaValue } ) => {
	const [ modalVisible, setModalVisible ] = useState( false );
	const [ parentTerms, setParentTerms ] = useState( null );
	const [ groupedTerms, setGroupedTerms ] = useState( {} );
	const [ message, setMessage ] = useState( null );

	useEffect(() => {
		apiFetch( {
			path: addQueryArgs( '/newspack-listings/v1/parents', {
				per_page: 100,
				post_id: postId,
			} ),
		} ).then( response => {
			setParentTerms( response );
		} );
	}, []);

	useEffect(() => {
		if ( Array.isArray( parentTerms ) ) {
			setGroupedTerms( groupBy( parentTerms, item => item.label ) );
		}
	}, [ parentTerms ]);

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__parent-listings"
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
						isListing() ? __( 'parent', 'newspack-listings' ) : __( 'related', 'newspack-listings' )
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
						__( 'Manage %s Listings' ),
						isListing() ? __( 'Parent', 'newspack-listings' ) : __( 'Related', 'newspack-listings' )
					) }
				</Button>
			) }
			{ modalVisible && (
				<Modal
					className="newspack-listings__modal"
					title={ sprintf(
						__( 'Manage %s Listings' ),
						isListing() ? __( 'Parent', 'newspack-listings' ) : __( 'Related', 'newspack-listings' )
					) }
					onRequestClose={ () => setModalVisible( false ) }
				>
					{ null === parentTerms ? (
						<Spinner />
					) : (
						Object.keys( groupedTerms ).map( parentLabel => (
							<Fragment key={ parentLabel }>
								{ message && <Notice { ...message } /> }
								<h4 className="newspack-listings__term-title">{ parentLabel }</h4>
								{ groupedTerms[ parentLabel ].map( term => (
									<Button
										className="newspack-listings__term-button"
										key={ term.id }
										isTertiary
										onClick={ async () => {
											setMessage( null );
											setParentTerms( parentTerms.filter( item => item.id !== term.id ) ); // Optimistically update state.

											const response = await apiFetch( {
												path: addQueryArgs( '/newspack-listings/v1/parents', {
													post_id: postId,
													removed: term.id,
													removed_taxonomy: term.type,
												} ),
												method: 'POST',
											} );

											if ( true === response ) {
												setMessage( {
													status: 'success',
													children: __( 'Parent updated.', 'newspack-listings' ),
													isDismissible: false,
												} );
											} else {
												setMessage( {
													status: 'error',
													children: __( 'Error udpating parent.', 'newspack-listings' ),
													isDismissible: false,
												} );
											}
										} }
									>
										{ term.name }
									</Button>
								) ) }
							</Fragment>
						) )
					) }
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
