/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';

export const PriceEditor = ( { attributes, isSelected, setAttributes } ) => {
	const { currencies = {}, currency: defaultCurrency = 'USD' } = window.newspack_listings_data;
	const locale = window.navigator?.language || 'en-US';
	const { currency, formattedPrice, price, showDecimals } = attributes;

	useEffect(() => {
		if ( ! formattedPrice ) {
			setAttributes( {
				formattedPrice: new Intl.NumberFormat( locale, {
					style: 'currency',
					currency: currency || defaultCurrency,
					minimumFractionDigits: showDecimals ? 2 : 0,
					maximumFractionDigits: showDecimals ? 2 : 0,
				} ).format( price ),
			} );
		}
	}, [ formattedPrice ]);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Price Settings' ) }>
					{ 0 < Object.keys( currencies ).length && (
						<SelectControl
							label={ __( 'Select currency', 'newspack-listings' ) }
							value={ currency || defaultCurrency }
							onChange={ value =>
								setAttributes( {
									currency: value,
									formattedPrice: new Intl.NumberFormat( locale, {
										style: 'currency',
										currency: value,
										minimumFractionDigits: showDecimals ? 2 : 0,
										maximumFractionDigits: showDecimals ? 2 : 0,
									} ).format( price ),
								} )
							}
							options={ Object.keys( currencies )
								.map( _currency => {
									return {
										value: _currency,
										label: `${ currencies[ _currency ] } (${ _currency })`,
									};
								} )
								.sort( ( a, b ) => a.label.toUpperCase().localeCompare( b.label.toUpperCase() ) ) }
						/>
					) }
					<PanelRow>
						<ToggleControl
							className="newspack-listings__decimals-toggle"
							label={ __( 'Show decimals', 'newspack-listings' ) }
							help={ __(
								'If disabled, the price shown will be rounded to the nearest integer.',
								'newspack-listings'
							) }
							checked={ showDecimals }
							onChange={ value =>
								setAttributes( {
									showDecimals: value,
									formattedPrice: new Intl.NumberFormat( locale, {
										style: 'currency',
										currency: currency || defaultCurrency,
										minimumFractionDigits: value ? 2 : 0,
										maximumFractionDigits: value ? 2 : 0,
									} ).format( price ),
								} )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<h2 className="newspack-listings__price">
				{ isSelected ? (
					<TextControl
						label={ sprintf(
							__( 'Enter price in %s', 'newspack-listings' ),
							currency || defaultCurrency
						) }
						type="number"
						placeholder={ sprintf(
							__( 'Price in %s', 'newspack-listings' ),
							currency || defaultCurrency
						) }
						value={ price }
						onChange={ value => {
							const newPrice = parseFloat( value < 0 ? 0 : value );
							setAttributes( {
								price: newPrice,
								formattedPrice: new Intl.NumberFormat( locale, {
									style: 'currency',
									currency: currency || defaultCurrency,
									minimumFractionDigits: showDecimals ? 2 : 0,
									maximumFractionDigits: showDecimals ? 2 : 0,
								} ).format( newPrice ),
							} );
						} }
					/>
				) : (
					<>{ formattedPrice }</>
				) }
			</h2>
		</>
	);
};
