/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import './style.scss';

const SidebarComponent = props => {
	if ( props.postType !== 'newspack_lst_curated' ) {
		return null;
	}

	const { meta, updateMetaValue } = props;
	const {
		newspack_listings_show_map,
		newspack_listings_show_numbers,
		newspack_listings_show_sort_by_date,
	} = meta;

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__editor-sidebar"
			name="newspack-listings"
			title={ __( 'Curated List Settings', 'newspack-listings' ) }
		>
			<ToggleControl
				className="newspack-listings__toggle-control"
				label={ __( 'Show numbers?', 'newspack-listings' ) }
				help={ __( 'Display numbers for the items in this list.' ) }
				checked={ newspack_listings_show_numbers }
				onChange={ value => updateMetaValue( 'newspack_listings_show_numbers', value ) }
			/>
			<ToggleControl
				className="newspack-listings__toggle-control"
				label={ __( 'Show map?', 'newspack-listings' ) }
				help={ __( 'Display a map with this list if at least one listing has geolocation data.' ) }
				checked={ newspack_listings_show_map }
				onChange={ value => updateMetaValue( 'newspack_listings_show_map', value ) }
			/>

			<ToggleControl
				className="newspack-listings__toggle-control"
				label={ __( 'Show sort-by-date controls?', 'newspack-listings' ) }
				help={ __( 'Display sort-by-date controls (only applicable to lists of events).' ) }
				checked={ newspack_listings_show_sort_by_date }
				onChange={ value => updateMetaValue( 'newspack_listings_show_sort_by_date', value ) }
			/>
		</PluginDocumentSettingPanel>
	);
};

const mapStateToProps = select => {
	const { getCurrentPostType, getEditedPostAttribute } = select( 'core/editor' );

	return {
		meta: getEditedPostAttribute( 'meta' ),
		postType: getCurrentPostType(),
		title: getEditedPostAttribute( 'title' ),
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
