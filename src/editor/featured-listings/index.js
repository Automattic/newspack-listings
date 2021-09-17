/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	DatePicker,
	PanelRow,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { dateI18n } from '@wordpress/date';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

const FeaturedListingsComponent = ( { meta, updateMetaValue } ) => {
	const {
		newspack_listings_featured,
		newspack_listings_featured_priority,
		newspack_listings_featured_expires,
	} = meta;

	return (
		<PluginDocumentSettingPanel
			className="newspack-listings__editor-sidebar-featured"
			name="newspack-listings-featured"
			title={ __( 'Featured Listing Settings', 'newspack-listings' ) }
		>
			<PanelRow>
				<ToggleControl
					className={ 'newspack-listings__toggle-control' }
					label={ __( 'Featured Listing', 'newspack-listings' ) }
					help={ sprintf(
						__( 'This listing is %sfeatured.', 'newspack-listings' ),
						newspack_listings_featured
							? __( '', 'newspack-listings' )
							: __( 'not ', 'newspack-listings' )
					) }
					checked={ newspack_listings_featured }
					onChange={ value => updateMetaValue( 'newspack_listings_featured', value ) }
				/>
			</PanelRow>
			{ newspack_listings_featured && (
				<>
					<PanelRow>
						<RangeControl
							label={ __( 'Priority Level', 'newspack-listings' ) }
							help={ __(
								'Relative importance of the featured item. Higher numbers mean higher priority.',
								'newspack-listings'
							) }
							value={ newspack_listings_featured_priority }
							onChange={ value => updateMetaValue( 'newspack_listings_featured_priority', value ) }
							min={ 1 }
							max={ 9 }
							required
						/>
					</PanelRow>
					<PanelRow>
						<BaseControl
							id="newspack-listings__featured-listing-expiration"
							label={ __( 'Expiration Date', 'newspack-listings' ) }
						>
							<DatePicker
								currentDate={
									newspack_listings_featured_expires
										? new Date( newspack_listings_featured_expires )
										: null
								}
								onMonthPreviewed={ () => {} }
								onChange={ value => {
									// Convert value to midnight in the local timezone.
									const date = new Date( value );
									const midnight = new Date( date.getFullYear(), date.getMonth(), date.getDate() );
									updateMetaValue(
										'newspack_listings_featured_expires',
										dateI18n( 'Y-m-d\\TH:i:s', midnight )
									);
								} }
							/>
							{ newspack_listings_featured_expires && (
								<Button
									isLink
									onClick={ () => updateMetaValue( 'newspack_listings_featured_expires', '' ) }
								>
									{ __( 'Reset', 'newspack-listings' ) }
								</Button>
							) }
						</BaseControl>
					</PanelRow>
				</>
			) }
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

export const FeaturedListings = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( FeaturedListingsComponent );
