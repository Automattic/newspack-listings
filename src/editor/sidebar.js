/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, ToggleControl } from '@wordpress/components';
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
	const { newspack_listings_show_map, newspack_listings_type } = meta;

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__editor-sidebar"
			name="newspack-listings"
			title={ __( 'Curated List Settings', 'newspack-listings' ) }
		>
			<SelectControl
				className="newspack-listings__select-control"
				label={ __( 'Listing Type', 'newspack-listings' ) }
				value={ newspack_listings_type || 'newspack_lst_generic' }
				options={ [
					{ value: 'newspack_lst_generic', label: __( 'Generic', 'newspack-listings' ) },
					{ value: 'newspack_lst_place', label: __( 'Place', 'newspack-listings' ) },
					{ value: 'newspack_lst_mktplce', label: __( 'Marketplace', 'newspack-listings' ) },
					{ value: 'newspack_lst_event', label: __( 'Event', 'newspack-listings' ) },
				] }
				onChange={ value => updateMetaValue( 'newspack_listings_type', value ) }
				help={ __( 'Select the type of list to be shown.', 'newspack-listings' ) }
			/>
			<ToggleControl
				className="newspack-listings__toggle-control"
				label={ __( 'Show map?', 'newspack-listings' ) }
				help={ __( 'Display a map with this list if at least one listing has geolocation data.' ) }
				checked={ newspack_listings_show_map }
				onChange={ value => updateMetaValue( 'newspack_listings_show_map', value ) }
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
