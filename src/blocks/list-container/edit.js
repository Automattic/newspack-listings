/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

export const ListContainerEditor = ( { clientId } ) => {
	const innerBlocks = useSelect( select => {
		return select( 'core/block-editor' ).getBlocksByClientId( clientId )[ 0 ].innerBlocks || [];
	} );

	return (
		<div className="newspack-listings__list-container">
			{ 0 === innerBlocks.length && (
				<Notice className="newspack-listings__info" status="info" isDismissible={ false }>
					{ __( 'This list is empty. Click the [+] button to add some listings.' ) }
				</Notice>
			) }
			<InnerBlocks
				allowedBlocks={ [
					'newspack-listings/event',
					'newspack-listings/generic',
					'newspack-listings/marketplace',
					'newspack-listings/place',
				] }
				renderAppender={ () => <InnerBlocks.ButtonBlockAppender /> }
			/>
		</div>
	);
};
