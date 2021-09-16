/* eslint-disable */

/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody, PanelRow, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './editor.scss';

export const SelfServeListingsEditor = ( { attributes, clientId, setAttributes } ) => {
	const [ selectedType, setSelectedType ] = useState( 'single' );
	const { allowSubscription, buttonText } = attributes;

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
							<div className="input-container">Single listing form fields</div>
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
								<div className="input-container">Subscription listing form fields</div>
							</div>
						) }
					</div>
					<button type="submit" onClick={ e => e.preventDefault() }>
						<RichText
							onChange={ value => setAttributes( { buttonText: value } ) }
							placeholder={ __( 'Button text…', 'newspack-blocks' ) }
							value={ buttonText }
							tagName="span"
						/>
					</button>
				</form>
			</div>
		</>
	);
};
