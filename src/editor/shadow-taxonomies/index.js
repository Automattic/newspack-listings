/**
 * Filtered taxonomy UI for Listings shadow taxonomies.
 * Replaces the default "add new" buttons with a link to create a new post of the corresponding type.
 */

/**
 * Internal dependencies
 */
import { ParentListings } from './parent-listings';
import { ChildListings } from './child-listings';
import './style.scss';

export const ShadowTaxonomies = () => {
	return (
		<>
			<ParentListings />
			<ChildListings />
		</>
	);
};
