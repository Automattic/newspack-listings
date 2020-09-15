/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies.
 */
import AutocompleteTokenField from './autocomplete-tokenfield';

class QueryControls extends Component {
	state = {
		showAdvancedFilters: false,
	};

	fetchListingSuggestions = search => {
		const { listingType, listItems } = this.props;
		return apiFetch( {
			path: addQueryArgs( '/newspack-listings/v1/listings', {
				search,
				per_page: 20,
				_fields: 'id,title',
				type: listingType,
			} ),
		} ).then( function( posts ) {
			// Only show suggestions if they aren't already in the list.
			const result = posts.reduce( ( acc, post ) => {
				if ( listItems.indexOf( post.id ) < 0 && listItems.indexOf( post.id.toString() ) < 0 ) {
					acc.push( {
						value: post.id,
						label: decodeEntities( post.title ) || __( '(no title)', 'newspack-listings' ),
					} );
				}

				return acc;
			}, [] );
			return result;
		} );
	};

	fetchSavedPosts = postIDs => {
		return apiFetch( {
			path: addQueryArgs( 'newspack-listings/v1/listings', {
				per_page: 100,
				include: postIDs.join( ',' ),
				_fields: 'id,title',
			} ),
		} ).then( function( posts ) {
			return posts.map( post => ( {
				value: post.id,
				label: decodeEntities( post.title ) || __( '(no title)', 'newspack-listings' ),
			} ) );
		} );
	};

	render = () => {
		const { label, selectedPost, onChange } = this.props;

		return [
			<AutocompleteTokenField
				key="listings"
				tokens={ [ selectedPost ] }
				onChange={ onChange }
				fetchSuggestions={ this.fetchListingSuggestions }
				fetchSavedInfo={ postIDs => this.fetchSavedPosts( postIDs ) }
				label={ label }
				help={ __(
					'Begin typing post title, click autocomplete result to select.',
					'newspack-listings'
				) }
			/>,
		];
	};
}

QueryControls.defaultProps = {
	selectedPost: 0,
};

export default QueryControls;
