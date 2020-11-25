/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	DatePicker,
	DateTimePicker,
	PanelRow,
	ToggleControl,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { withDispatch, withSelect } from '@wordpress/data';

const EventDatesComponent = ( { createNotice, meta, updateMetaValue } ) => {
	const { newspack_listings_event_dates } = meta;

	// Replace a single date item in the array of dates.
	const updateDates = ( updated, index ) => {
		return updateMetaValue(
			'newspack_listings_event_dates',
			newspack_listings_event_dates.map( ( d, i ) => ( index !== i ? d : updated ) )
		);
	};

	return (
		<Fragment>
			{ newspack_listings_event_dates.map( ( dates, index ) => {
				const {
					endDate = '',
					showEnd = false,
					showEndTime = false,
					startDate = '',
					showStartTime = false,
				} = dates;

				const StartDatePicker = showStartTime ? DateTimePicker : DatePicker;
				const EndDatePicker = showEndTime ? DateTimePicker : DatePicker;

				return (
					<Fragment key={ index }>
						<hr />
						<PanelRow>
							<BaseControl
								id={ `event-start-date-${ index }` }
								label={ __( 'Event start date ' + ( index + 1 ), 'newspack-listings' ) }
							>
								<ToggleControl
									className="newspack-listings__event-time-toggle"
									label={ __( 'Show time', 'newspack-listings' ) }
									checked={ showStartTime }
									onChange={ value => {
										updateDates(
											{
												endDate,
												showEnd,
												showEndTime,
												startDate,
												showStartTime: value,
											},
											index
										);
									} }
								/>
								<StartDatePicker
									currentDate={ startDate ? new Date( startDate ) : null }
									is12Hour={ true }
									onChange={ value => {
										updateDates(
											{
												endDate,
												showEnd,
												showEndTime,
												startDate: value,
												showStartTime,
											},
											index
										);
									} }
								/>
							</BaseControl>
						</PanelRow>
						{ showEnd && (
							<Fragment>
								<hr />
								<PanelRow>
									<BaseControl
										id={ `event-end-date-${ index }` }
										label={ __( 'Event end date ' + ( index + 1 ), 'newspack-listings' ) }
									>
										<ToggleControl
											className="newspack-listings__event-time-toggle"
											label={ __( 'Show time', 'newspack-listings' ) }
											checked={ showEndTime }
											onChange={ value => {
												updateDates(
													{
														endDate,
														showEnd,
														showEndTime: value,
														startDate,
														showStartTime,
													},
													index
												);
											} }
										/>
										<EndDatePicker
											currentDate={ endDate ? new Date( endDate ) : null }
											is12Hour={ true }
											onChange={ value => {
												if (
													! value ||
													( startDate && 0 < new Date( value ) - new Date( startDate ) )
												) {
													return updateDates(
														{
															endDate: value,
															showEnd,
															showEndTime,
															startDate,
															showStartTime,
														},
														index
													);
												}

												createNotice(
													'warning',
													__( 'Event end must be after event start.', 'newspack-listings' ),
													{
														id: 'newspack-listings__date-error',
														isDismissible: true,
														type: 'default',
													}
												);
											} }
										/>
									</BaseControl>
								</PanelRow>
							</Fragment>
						) }
						<PanelRow>
							<Button
								isTertiary
								onClick={ () => {
									const update = {
										endDate,
										showEnd: true,
										showEndTime,
										startDate,
										showStartTime,
									};

									// If hiding, clear the field.
									if ( showEnd ) {
										update.endDate = '';
										update.showEnd = false;
									}

									updateDates( update, index );
								} }
							>
								{ sprintf( __( '%s end date', 'newspack-listings' ), showEnd ? 'Hide' : 'Show' ) }
							</Button>
						</PanelRow>
						<PanelRow>
							<Button
								isSecondary
								onClick={ () => {
									const updated = [ ...newspack_listings_event_dates ];

									updated.splice( index, 1 );

									updateMetaValue( 'newspack_listings_event_dates', updated );
								} }
							>
								{ __( 'Remove dates', 'newspack-listings' ) }
							</Button>
						</PanelRow>
					</Fragment>
				);
			} ) }
			<hr />
			{ 0 === newspack_listings_event_dates.length && (
				<PanelRow>
					<p>
						<em>{ __( 'This event has no dates.', 'newspack-listings' ) }</em>
					</p>
				</PanelRow>
			) }
			<PanelRow>
				<Button
					isPrimary
					onClick={ () =>
						updateMetaValue( 'newspack_listings_event_dates', [
							...newspack_listings_event_dates,
							{
								endDate: '',
								showEnd: false,
								showEndTime: false,
								startDate: '',
								showStartTime: false,
							},
						] )
					}
				>
					{ __( 'Add event dates', 'newspack-listings' ) }
				</Button>
			</PanelRow>
		</Fragment>
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
	const { createNotice } = dispatch( 'core/notices' );

	return {
		createNotice,
		updateMetaValue: ( key, value ) => editPost( { meta: { [ key ]: value } } ),
	};
};

export const EventDates = compose( [
	withSelect( mapStateToProps ),
	withDispatch( mapDispatchToProps ),
] )( EventDatesComponent );
