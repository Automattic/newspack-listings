/**
 * Filtered taxonomy UI for Listings shadow taxonomies.
 * Replaces the default "add new" buttons with a link to create a new post of the corresponding type.
 */

/**
 * Internal dependencies
 */
import { ParentListings } from './parent-listings';
import { ChildListings } from './child-listings';
import { getPostTypeByTaxonomy, getTaxonomyForPostType } from '../utils';
import './style.scss';

export const ShadowTaxonomies = () => {
	const { post_type: postType, taxonomies } = window?.newspack_listings_data;
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
			{ canHaveParents && <ParentListings /> }
			{ canHaveChildren && <ChildListings /> }
		</>
	);
};
