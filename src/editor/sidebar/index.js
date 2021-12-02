/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { ExternalLink, PanelRow, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import { isListing } from '../utils';
import './style.scss';

const SidebarComponent = ( { meta, updateMetaValue } ) => {
	const { post_type_label: postTypeLabel, post_types: postTypes } = window.newspack_listings_data;
	const { newspack_listings_hide_author, newspack_listings_hide_publish_date } = meta;

	if ( ! postTypes ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__editor-sidebar"
			name="newspack-listings"
			title={ sprintf(
				// Translators: Listing post type sidebar settings label.
				__( '%s Settings', 'newspack-listings' ),
				isListing() ? postTypeLabel : __( 'Newspack Listings', 'newspack-listings' )
			) }
		>
			<p>
				<em>
					{ __( 'Overrides ', 'newspack-listings' ) }
					<ExternalLink href="/wp-admin/admin.php?page=newspack-listings-settings-admin">
						{ __( 'global settings', 'newspack-listings' ) }
					</ExternalLink>
				</em>
			</p>
			<PanelRow>
				<ToggleControl
					className={ 'newspack-listings__toggle-control' }
					label={ __( 'Hide listing author', 'newspack-listings' ) }
					help={ sprintf(
						// Translators: Show or hide author byline toggle label.
						__( '%s the author byline for this listing.', 'newspack-listings' ),
						newspack_listings_hide_author
							? __( 'Hide', 'newspack-listings' )
							: __( 'Show', 'newspack-listings' )
					) }
					checked={ newspack_listings_hide_author }
					onChange={ value => updateMetaValue( 'newspack_listings_hide_author', value ) }
				/>
			</PanelRow>
			<PanelRow>
				<ToggleControl
					className={ 'newspack-listings__toggle-control' }
					label={ __( 'Hide publish date', 'newspack-listings' ) }
					help={ sprintf(
						// Translators: Show or hide publish date toggle label.
						__( '%s the publish and updated dates for this listing.', 'newspack-listings' ),
						newspack_listings_hide_publish_date
							? __( 'Hide', 'newspack-listings' )
							: __( 'Show', 'newspack-listings' )
					) }
					checked={ newspack_listings_hide_publish_date }
					onChange={ value => updateMetaValue( 'newspack_listings_hide_publish_date', value ) }
				/>
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
};

const mapStateToProps = select => {
	const { getEditedPostAttribute } = select( 'core/editor' );

	return {
		meta: getEditedPostAttribute( 'meta' ),
	};
};

const mapDispatchToProps = dispatch => {
	const { editPost } = dispatch( 'core/editor' );

	return {
		updateMetaValue: ( key, value ) => editPost( { meta: { [ key ]: value } } ),
	};
};

export const Sidebar = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( SidebarComponent );
