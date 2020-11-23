/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

const ListContainerEditorComponent = ( {
	clientId,
	innerBlocks,
	isSelected,
	parent,
	setAttributes,
} ) => {
	const parentAttributes = parent.attributes || {};
	const { queryMode, queryOptions, isSelected: parentIsSelected, showSortUi } = parentAttributes;
	const { sortBy, order } = queryOptions;

	// Sync parent attributes to list container attributes, so that we can use parent attributes in the PHP render callback.
	useEffect(() => {
		setAttributes( { ...parentAttributes } );
	}, [ JSON.stringify( parentAttributes ) ]);

	if ( queryMode && ! showSortUi ) {
		return null;
	}

	// Available sort options. "Default" is only available for lists in specific listing mode.
	const sortOptions = [
		{ value: '', label: __( 'Sort by', 'newspack-listings' ) },
		{ value: 'date', label: __( 'Publish Date', 'newspack-listings' ) },
		{ value: 'title', label: __( 'Title', 'newspack-listings' ) },
		{ value: 'type', label: __( 'Listing Type', 'newspack-listings' ) },
		{ value: 'author', label: __( 'Author', 'newspack-listings' ) },
	];

	return (
		<div className="newspack-listings__list-container">
			{ ! queryMode && innerBlocks && 0 === innerBlocks.length && (
				<Notice className="newspack-listings__info" status="info" isDismissible={ false }>
					{ __( 'This list is empty. Click the [+] button to add some listings.' ) }
				</Notice>
			) }
			{ showSortUi && (
				<div className="newspack-listings__sort-ui">
					<section>
						<label
							className="newspack-listings__sort-ui-label"
							htmlFor={ `newspack-listings__sort-by-${ clientId }` }
						>
							{ __( 'Sort by:', 'newspack-listings' ) }
						</label>
						<select
							disabled
							className="newspack-listings__sort-select-control"
							id={ `newspack-listings__sort-by-${ clientId }` }
						>
							{ sortOptions.map( ( option, index ) => (
								<option
									key={ index }
									value={ option.value }
									selected={ queryMode && sortBy === option.value }
								>
									{ option.label }
								</option>
							) ) }
						</select>
					</section>

					<section>
						<label
							className="newspack-listings__sort-ui-label"
							htmlFor={ `sort-buttons-${ clientId }` }
						>
							{ __( 'Sort order:', 'newspack-listings' ) }
						</label>

						<div id={ `sort-buttons-${ clientId }` }>
							<input
								disabled
								id={ `sort-ascending-${ clientId }` }
								type="radio"
								name="newspack-listings__sort-order"
								value="ASC"
								checked={ queryMode && order === 'ASC' }
							/>
							<label htmlFor={ `sort-ascending-${ clientId }` }>
								{ __( 'Ascending', 'newspack-listings' ) }
							</label>
						</div>

						<div>
							<input
								disabled
								id={ `sort-descending-${ clientId }` }
								type="radio"
								name="newspack-listings__sort-order"
								value="DESC"
								checked={ queryMode && order === 'DESC' }
							/>
							<label htmlFor={ `sort-descending-${ clientId }` }>
								{ __( 'Descending', 'newspack-listings' ) }
							</label>
						</div>
					</section>
				</div>
			) }
			<InnerBlocks
				allowedBlocks={ [
					'newspack-listings/event',
					'newspack-listings/generic',
					'newspack-listings/marketplace',
					'newspack-listings/place',
				] }
				renderAppender={ () =>
					queryMode || ( ! isSelected && ! parentIsSelected ) ? null : (
						<InnerBlocks.ButtonBlockAppender />
					)
				}
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
