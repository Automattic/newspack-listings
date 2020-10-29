/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';
import { Fragment, Component } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies.
 */
import AutocompleteTokenField from './autocomplete-tokenfield';

class QueryControls extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			error: null,
			loading: true,
			suggestions: [],
		};
	}

	/**
	 * Fetch recent posts to show as suggestions.
	 */
	componentDidMount() {
		this.fetchListingSuggestions().then( suggestions => {
			if ( suggestions && 0 < suggestions.length ) {
				this.setState( { suggestions } );
			}
		} );
	}

	/**
	 * Fetch posts to show as suggestions.
	 *
	 * @param {string} search An optional search string to use to query posts by title.
	 */
	fetchListingSuggestions = search => {
		const { listingType, listItems } = this.props;
		return apiFetch( {
			path: addQueryArgs( '/newspack-listings/v1/listings', {
				search,
				per_page: 10,
				_fields: 'id,title',
				type: listingType,
			} ),
		} )
			.then( posts => {
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
			} )
			.catch( e => {
				this.setState( {
					error:
						e.message ||
						__( 'An error occurred. Try searching for a listing by title.', 'newspack-listings' ),
				} );
			} )
			.finally( () => this.setState( { loading: false } ) );
	};

	/**
	 * If there are saved posts, fetch post info to be tokenized and displayed.
	 *
	 * @param {Array} postIDs Array of post IDs.
	 */
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

	/**
	 * Render a single suggestion object that can be clicked to select it immediately.
	 *
	 * @param {Object} suggestion Suggestion object with value and label keys.
	 * @param {number} index Index of this suggestion in the array.
	 */
	renderSuggestion( suggestion, index ) {
		const { onChange } = this.props;
		return (
			<Button isLink key={ index } onClick={ () => onChange( [ suggestion.value ] ) }>
				{ suggestion.label }
			</Button>
		);
	}

	/**
	 * Render a list of suggestions that can be clicked to select instead of searching by title.
	 */
	renderSuggestions() {
		const { listingTypeSlug } = this.props;
		const { suggestions } = this.state;

		return (
			<Fragment>
				<span className="newspack-listings__search-suggestions-label">
					{ sprintf(
						__( 'Or, select a recent %s listing:', 'newspack-listings' ),
						listingTypeSlug
					) }
				</span>
				<div className="newspack-listings__search-suggestions">
					{ suggestions.map( this.renderSuggestion.bind( this ) ) }
				</div>
			</Fragment>
		);
	}

	render = () => {
		const { label, listingType, listingTypeSlug, selectedPost, onChange } = this.props;
		const { error, loading, suggestions } = this.state;

		return (
			<Fragment>
				{ error && (
					<Notice className="newspack-listings__error" status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }
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
				/>
				<hr />
				{ 0 < suggestions.length && this.renderSuggestions() }
				{ 0 === suggestions.length && ! loading && (
					<Fragment>
						<span className="newspack-listings__search-suggestions-label">
							{ sprintf(
								__( 'You don’t have any %s listings yet.', 'newspack-listings' ),
								listingTypeSlug
							) }
						</span>
						<Button
							isSecondary
							href={ `/wp-admin/post-new.php?post_type=${ listingType }` }
							target="_blank"
						>
							{ sprintf( __( 'Create new %s listing', 'newspack-listing' ), listingTypeSlug ) }
						</Button>
					</Fragment>
				) }
			</Fragment>
		);
	};
}

QueryControls.defaultProps = {
	selectedPost: 0,
};

export default QueryControls;
