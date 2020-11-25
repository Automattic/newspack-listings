/* eslint-disable jsx-a11y/anchor-is-valid */

/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { date } from '@wordpress/date';
import { Fragment, RawHTML } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

export const Listing = ( { attributes, error, post } ) => {
	// Parent Curated List block attributes.
	const { showAuthor, showCategory, showTags, showExcerpt, showImage, showCaption } = attributes;
	const { date_format, time_format } = window.newspack_listings_data;
	const { newspack_listings_event_dates } = post.meta;

	// Render event date(s) and time(s).
	const renderDates = () => {
		return (
			<ul className="newspack-listings__event-dates">
				{ newspack_listings_event_dates.map( ( dates, index ) => {
					const {
						endDate = '',
						showEnd = false,
						showEndTime = false,
						startDate = '',
						showStartTime = false,
					} = dates;
					return (
						<li key={ index } className="newspack-listings__event-start-time">
							<time dateTime={ startDate }>
								{ date(
									showStartTime ? `${ date_format } ${ time_format }` : date_format,
									startDate
								) }
							</time>
							{ showEnd && (
								<Fragment>
									{ _x( ' – ', 'Date range separator', 'newspack-listings' ) }
									<time dateTime={ endDate }>
										{ date(
											showEndTime ? `${ date_format } ${ time_format }` : date_format,
											endDate
										) }
									</time>
								</Fragment>
							) }
						</li>
					);
				} ) }
			</ul>
		);
	};

	return (
		<div className="newspack-listings__listing-post entry-wrapper">
			{ error && (
				<Notice className="newspack-listings__error" status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			{ showImage && post && post.media && post.media.image && (
				<figure className="newspack-listings__listing-featured-media">
					<img
						className="newspack-listings__listing-thumbnail"
						src={ post.media.image }
						alt={ post.media.caption || post.title }
					/>
					{ showCaption && post.media.caption && (
						<figcaption className="newspack-listings__listing-caption">
							{ post.media.caption }
						</figcaption>
					) }
				</figure>
			) }
			{ post && post.title && (
				<div className="newspack-listings__listing-meta">
					{ showCategory && post.category.length && ! post.newspack_post_sponsors && (
						<div className="cat-links">
							{ post.category.map( ( category, index ) => (
								<Fragment key="index">
									{ decodeEntities( category.name ) }
									{ index + 1 < post.category.length && ', ' }
								</Fragment>
							) ) }
						</div>
					) }
					<h3 className="newspack-listings__listing-title">{ decodeEntities( post.title ) }</h3>
					{ showAuthor && post.author && (
						<cite>{ __( 'By', 'newpack-listings' ) + ' ' + decodeEntities( post.author ) }</cite>
					) }
					{ newspack_listings_event_dates &&
						0 < newspack_listings_event_dates.length &&
						renderDates() }
					{ showExcerpt && post.excerpt && <RawHTML>{ post.excerpt }</RawHTML> }

					{ showTags && post.tags.length && (
						<p>
							<strong>{ __( 'Tagged: ', 'newspack-listings' ) }</strong>
							{ post.tags.map( ( tag, index ) => (
								<Fragment key="index">
									{ decodeEntities( tag.name ) }
									{ index + 1 < post.tags.length && ', ' }
								</Fragment>
							) ) }
						</p>
					) }
				</div>
			) }
		</div>
	);
};
