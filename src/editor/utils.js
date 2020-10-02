/**
 * Util functions for Newspack Listings.
 */

/**
 * Check if the current post in the editor is a listing CPT.
 *
 * @return {bool}
 */
export const isListing = () => {
	if ( ! window.newspack_listings_data ) {
		return false;
	}

	const { post_type, post_types } = window.newspack_listings_data;

	for ( const slug in post_types ) {
		if ( post_types.hasOwnProperty( slug ) && post_type === post_types[ slug ] ) {
			return true;
		}
	}

	return false;
};
