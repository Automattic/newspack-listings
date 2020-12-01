/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post'; /**

/**
* Internal dependencies
*/
import { isListing } from '../utils';

const SidebarComponent = ( { meta, updateMetaValue } ) => {
	const { meta_fields, post_type_label, post_types } = window.newspack_listings_data;

	if ( ! post_types || ! isListing() ) {
		return null;
	}

	// Render a meta field with the right component.
	const renderMetaField = ( fieldName, i ) => {
		const field = meta_fields[ fieldName ];

		if ( 'toggle' === field.type ) {
			return (
				<ToggleControl
					key={ i }
					className={ `newspack-listings__${ field.type }-control` }
					label={ field.settings.label }
					help={ field.settings.description }
					checked={ meta[ fieldName ] }
					onChange={ value => updateMetaValue( fieldName, value ) }
				/>
			);
		} else if ( 'input' === field.type ) {
			return (
				<TextControl
					key={ i }
					className={ `newspack-listings__${ field.type }-control` }
					label={ field.settings.label }
					help={ field.settings.description }
					type="text"
					value={ meta[ fieldName ] }
					onChange={ value => updateMetaValue( fieldName, value ) }
				/>
			);
		}

		return null;
	};

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__editor-sidebar"
			name="newspack-listings"
			title={ post_type_label + ' ' + __( 'Settings', 'newspack-listings' ) }
		>
			{ Object.keys( meta_fields ).map( renderMetaField ) }
		</PluginDocumentSettingPanel>
	);
};

const mapStateToProps = select => {
	const { getEditedPostAttribute } = select( 'core/editor' );

	return {
		meta: getEditedPostAttribute( 'meta' ),
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
