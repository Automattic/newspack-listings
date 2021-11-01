/* eslint-disable */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import {
	BaseControl,
	CheckboxControl,
	Notice,
	PanelBody,
	PanelRow,
	ToggleControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './editor.scss';

const {
	self_serve_listing_types: singleListingTypes = [],
	self_serve_listing_expiration: singleExpirationPeriod = 30,
} = window.newspack_listings_data || {};

export const SelfServeListingsEditor = ( { attributes, clientId, setAttributes } ) => {
	const [ selectedType, setSelectedType ] = useState( 'single' );
	const [ error, setError ] = useState( null );
	const {
		allowedSingleListingTypes,
		allowSubscription,
		buttonText,
		singleDescription,
		subscriptionDescription,
	} = attributes;

	useEffect(() => {
		setAttributes( { clientId } );
	}, [ clientId ]);

	const classNames = [ 'newspack-listings__self-serve-form', 'wpbnbd' ];

	if ( ! allowSubscription ) {
		classNames.push( 'single-only' );
	}
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Self-Serve Listing Settings' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Allow subscriptions', 'newspack-listings' ) }
							help={ sprintf(
								__( 'Subscriptions are %senabled for this purchase form.', 'newspack-listings' ),
								allowSubscription ? '' : 'not '
							) }
							checked={ allowSubscription }
							onChange={ () => setAttributes( { allowSubscription: ! allowSubscription } ) }
						/>
					</PanelRow>
					<BaseControl
						id="newspack-listings-allowed-single-listing-types"
						help={ __(
							'Choose which listing types users are allowed to purchase.',
							'newspack-listings'
						) }
						label={ __( 'Allowed Single Listing Types', 'newspack-listings' ) }
					>
						{ singleListingTypes.map( listingType => {
							const isAllowed = allowedSingleListingTypes.reduce( ( acc, type ) => {
								if ( type.slug === listingType.slug ) {
									return true;
								}
								return acc;
							}, false );
							return (
								<PanelRow key={ listingType.slug }>
									<CheckboxControl
										label={ listingType.name }
										checked={ isAllowed }
										onChange={ value => {
											setError( null );
											if ( ( value && isAllowed ) || ( ! value && ! isAllowed ) ) {
												return false;
											}

											let newAllowedListingTypes = [ ...allowedSingleListingTypes ];

											if ( value ) {
												newAllowedListingTypes.push( listingType );
											} else {
												newAllowedListingTypes = allowedSingleListingTypes.filter(
													type => type.slug !== listingType.slug
												);
											}

											if ( 0 === newAllowedListingTypes.length ) {
												setError(
													__(
														'You must allow at least one listing type for purchase.',
														'newspack-listings'
													)
												);
												return false;
											}

											setAttributes( {
												allowedSingleListingTypes: newAllowedListingTypes,
											} );
										} }
									/>
								</PanelRow>
							);
						} ) }
					</BaseControl>
					{ error && (
						<Notice className="newspack-listings__error" status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<div className={ classNames.join( ' ' ) }>
				<form>
					<div className="frequencies">
						<div className="newspack-listings__form-tabs frequency">
							<input
								name="listing-purchase-type"
								className="newspack-listings__tab-input"
								id={ `listing-single-${ clientId }` }
								type="radio"
								value="listing-single"
								checked={ 'single' === selectedType || ! allowSubscription }
								onClick={ () => setSelectedType( 'single' ) }
							/>
							<label
								className="freq-label listing-single"
								htmlFor="listing-single"
								onClick={ () => setSelectedType( 'single' ) }
							>
								{ __( 'Single Listing' ) }
							</label>
							<div className="input-container listing-details">
								<RichText
									onChange={ value => setAttributes( { singleDescription: value } ) }
									placeholder={ __(
										'Description text for your single listing product…',
										'newspack-listings'
									) }
									value={ singleDescription }
									tagName="p"
								/>
								{ singleExpirationPeriod && (
									<p className="newspack-listings__help">
										{ sprintf(
											__(
												'Single-purchase listings expire %d days after the date of publication.',
												'newspack-listings'
											),
											singleExpirationPeriod
										) }
									</p>
								) }
								<hr />
								<h3>{ __( 'Listing Details', 'newspack-listings' ) }</h3>
								<label htmlFor={ `listing-title-single-${ clientId }` }>
									{ __( 'Listing Title', 'newspack-listings' ) }
								</label>
								<input
									type="text"
									id={ `listing-title-single-${ clientId }` }
									name="listing-title-single"
									value=""
									placeholder={ __( 'My Listing Title' ) }
								/>
								<label htmlFor={ `listing-type-${ clientId }` }>
									{ __( 'Listing Type', 'newspack-listings' ) }
								</label>
								<select id={ `${ clientId }` } name="listing-single-type">
									{ allowedSingleListingTypes.map( listingType => (
										<option key={ listingType.slug } value={ `listing-type-${ listingType.slug }` }>
											{ listingType.name }
										</option>
									) ) }
								</select>
								<input
									type="checkbox"
									id={ `listing-single-upgrade-${ clientId }` }
									name="listing-featured-upgrade"
								/>
								<label htmlFor={ `listing-single-upgrade-${ clientId }` }>
									{ __( 'Upgrade to a featured listing', 'newspack-listings' ) }
								</label>
								<p class="newspack-listings__help">
									{ __(
										'Featured listings appear first in lists, directory pages and search results.',
										'newspack-listings'
									) }
								</p>
							</div>
						</div>
						{ allowSubscription && (
							<div className="newspack-listings__form-tabs frequency">
								<input
									name="listing-purchase-type"
									className="newspack-listings__tab-input"
									id={ `listing-subscription-${ clientId }` }
									type="radio"
									value="listing-subscription"
									checked={ 'subscription' === selectedType }
									onClick={ () => setSelectedType( 'subscription' ) }
								/>
								<label
									className="freq-label listing-subscription"
									htmlFor="listing-subscription"
									onClick={ () => setSelectedType( 'subscription' ) }
								>
									{ __( 'Listing Subscription' ) }
								</label>
								<div className="input-container listing-details">
									<RichText
										onChange={ value => setAttributes( { subscriptionDescription: value } ) }
										placeholder={ __(
											'Description text for your subscription product…',
											'newspack-listings'
										) }
										value={ subscriptionDescription }
										tagName="p"
									/>
									<p className="newspack-listings__help">
										{ __(
											'Subscription listings remain live as long as the subscription is active.',
											'newspack-listings'
										) }
									</p>
									<hr />
									<h3>{ __( 'Listing Details', 'newspack-listings' ) }</h3>
									<label htmlFor={ `listing-title-subscription${ clientId }` }>
										{ __( 'Listing Title', 'newspack-listings' ) }
									</label>
									<input
										type="text"
										id={ `listing-title-subscription${ clientId }` }
										name="listing-title-subscription"
										value=""
										placeholder={ __( 'My Listing Title' ) }
									/>
									<input
										type="checkbox"
										id={ `listing-subscription-upgrade-${ clientId }` }
										name="listing-premium-upgrade"
									/>
									<label htmlFor={ `listing-subscription-upgrade-${ clientId }` }>
										{ __( 'Upgrade to a premium subscription', 'newspack-listings' ) }
									</label>
									<p class="newspack-listings__help">
										{ __(
											'A premium subscription upgrades your listing to "featured" status and lets you create up to 10 free Marketplace or Event listings.',
											'newspack-listings'
										) }
									</p>
								</div>
							</div>
						) }
					</div>
					<button type="submit" onClick={ e => e.preventDefault() }>
						<RichText
							onChange={ value => setAttributes( { buttonText: value } ) }
							placeholder={ __( 'Button text…', 'newspack-listings' ) }
							value={ buttonText }
							tagName="span"
						/>
					</button>
				</form>
			</div>
		</>
	);
};
