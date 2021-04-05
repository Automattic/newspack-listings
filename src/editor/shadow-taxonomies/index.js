/**
 * Filtered taxonomy UI for Listings shadow taxonomies.
 * Replaces the default "add new" buttons with a link to create a new post of the corresponding type.
 */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getPostTypeByTaxonomy, getTaxonomyLabel, isShadowTaxonomy } from '../utils';

export const ShadowTaxonomies = OriginalComponent => {
	return props => {
		const { slug } = props;

		// Bail if not a shadow taxonomy.
		if ( ! isShadowTaxonomy( slug ) ) {
			return <OriginalComponent { ...props } />;
		}

		const modifiedProps = { ...props, hasCreateAction: false };
		return (
			<div className="newspack-listings__shadow-taxonomy-control">
				<OriginalComponent { ...modifiedProps } />
				<Button
					isLink
					className="editor-post-taxonomies__hierarchical-terms-add"
					href={ `/wp-admin/post-new.php?post_type=${ getPostTypeByTaxonomy( slug ) }` }
				>
					{ sprintf( __( 'Add New %s', 'newspack-listings' ), getTaxonomyLabel( slug ) ) }
				</Button>
			</div>
		);
	};
};
