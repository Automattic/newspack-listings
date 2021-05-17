/**
 * WordPress dependencies
 */
import { ExternalLink, FormTokenField, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';

/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import { getPostTypeLabel, getPostTypeByTaxonomy, isListing } from '../utils';

export const TaxonomySearch = ( {
	fetchSaved = () => false,
	fetchSuggestions = () => false,
	postTitle = '',
	postType,
	savedIds,
	taxonomy,
	update,
} ) => {
	const { post_type_label: postTypeLabel } = window?.newspack_listings_data;
	const [ isLoading, setIsLoading ] = useState( false );
	const [ tokens, setTokens ] = useState( null );
	const label = taxonomy ? taxonomy.label : getPostTypeLabel( postType );
	const type = taxonomy ? taxonomy.name : postType;
	const [ suggestions, setSuggestions ] = useState( [] );
	const shadowPostType = taxonomy ? getPostTypeByTaxonomy( type ) : postType;

	useEffect(() => {
		if ( savedIds.length ) {
			setIsLoading( true );
			fetchSaved( savedIds, type )
				.then( results => {
					setTokens( results || [] );
					setIsLoading( false );
				} )
				.catch( () => setIsLoading( false ) );
		} else {
			setTokens( [] );
		}
	}, [ savedIds ]);

	const debouncedFetchSuggestions = debounce( input => {
		setIsLoading( true );
		fetchSuggestions( input, type )
			.then( results => {
				setSuggestions( results || [] );
				setIsLoading( false );
			} )
			.catch( () => setIsLoading( false ) );
	}, 500 );

	return (
		<div className="newspack-listings__shadow-taxonomy-panel">
			<FormTokenField
				disabled={ null === tokens }
				value={
					tokens
						? tokens
								.filter( token => decodeEntities( token.name ) !== postTitle ) // Filter out the post being edited.
								.map( token => decodeEntities( token.name ) )
						: []
				}
				onInputChange={ debouncedFetchSuggestions }
				onChange={ _items => {
					const updatedItems = ( tokens || [] )
						.concat( suggestions ) // Combine suggestions and existing tokens.
						.filter( item => -1 < _items.indexOf( decodeEntities( item.name ) ) ) // Filter out those in the input response.
						.reduce(
							( acc, item ) =>
								-1 < acc.map( _item => _item.id ).indexOf( item.id ) ? acc : [ ...acc, item ],
							[]
						); // Remove duplicates.
					const removedItems = ( tokens || [] ).filter(
						item => -1 === _items.indexOf( decodeEntities( item.name ) )
					);
					setTokens( updatedItems );
					update( updatedItems.map( item => item.id ), removedItems.map( item => item.id ) );
				} }
				suggestions={ suggestions
					.filter( suggestion => decodeEntities( suggestion.name ) !== postTitle ) // Filter out the post being edited.
					.map( suggestion => decodeEntities( suggestion.name ) ) }
				label={
					isListing()
						? sprintf( __( '%ss', 'newspack-listings' ), label )
						: sprintf(
								__( '%ss linked to this %s:', 'newspack-listings' ),
								label,
								postTypeLabel.toLowerCase()
						  )
				}
				__experimentalShowHowTo={ false }
			/>
			<ExternalLink href={ `/wp-admin/edit.php?post_status=publish&post_type=${ shadowPostType }` }>
				{ sprintf( __( 'View All %ss', 'newspack-listings' ), label ) }
			</ExternalLink>
			{ isLoading && <Spinner /> }
		</div>
	);
};
