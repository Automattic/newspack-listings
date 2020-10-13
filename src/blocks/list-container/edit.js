/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';

const ListContainerEditorComponent = ( { innerBlocks, parent } ) => {
	const parentAttributes = parent.attributes || {};
	const queryMode = parentAttributes.queryMode || false;

	return (
		<div className="newspack-listings__list-container">
			{ ! queryMode && innerBlocks && 0 === innerBlocks.length && (
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
				renderAppender={ () => ( queryMode ? null : <InnerBlocks.ButtonBlockAppender /> ) }
			/>
		</div>
	);
};

const mapStateToProps = ( select, ownProps ) => {
	const { clientId } = ownProps;
	const { getBlock, getBlockParents } = select( 'core/block-editor' );
	const innerBlocks = getBlock( clientId ).innerBlocks || [];
	const parentId = getBlockParents( clientId )[ 0 ] || null;
	const parent = getBlock( parentId );

	return {
		innerBlocks,
		parent,
	};
};

const mapDispatchToProps = dispatch => {
	const { insertBlock, removeBlocks, updateBlockAttributes } = dispatch( 'core/block-editor' );

	return {
		insertBlock,
		removeBlocks,
		updateBlockAttributes,
	};
};

export const ListContainerEditor = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( ListContainerEditorComponent );
