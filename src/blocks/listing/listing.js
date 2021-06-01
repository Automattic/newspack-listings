/* eslint-disable jsx-a11y/anchor-is-valid */

/**
 * WordPress dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { Fragment, RawHTML } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

export const Listing = ( { attributes, error, post } ) => {
	// Parent Curated List block attributes.
	const { showAuthor, showCategory, showTags, showExcerpt, showImage, showCaption } = attributes;
	const { meta } = post;
	const { sponsors = false, author = false } = meta;

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
					{ sponsors && 0 < sponsors.length && (
						<span className="cat-links sponsor-label">
							<span className="flag">{ sponsors[ 0 ].sponsor_flag }</span>
						</span>
					) }
					{ showCategory && post.category.length && ! sponsors && (
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
					{ sponsors && 0 < sponsors.length && (
						<div className="newspack-listings__sponsors">
							<span className="sponsor-logos">
								{ sponsors.map( sponsor => {
									return (
										<img
											key={ sponsor.sponsor_id }
											src={ sponsor.sponsor_logo.src }
											width={ sponsor.sponsor_logo.img_width }
											height={ sponsor.sponsor_logo.img_height }
											alt={ sponsor.sponsor_name }
										/>
									);
								} ) }
							</span>
							<span className="sponsor-byline">
								{ sponsors.map( ( sponsor, index ) =>
									sprintf(
										'%s%s%s%s',
										0 === index ? sponsor.sponsor_byline + ' ' : '',
										1 < sponsors.length && index + 1 === sponsors.length
											? __( ' and ', 'newspack-listings' )
											: '',
										sponsor.sponsor_name,
										2 < sponsors.length && index + 1 < sponsors.length
											? _x( ', ', 'separator character', 'newspack-listings' )
											: ''
									)
								) }
							</span>
						</div>
					) }
					{ showAuthor && author && ! sponsors && (
						<cite>{ __( 'By', 'newpack-listings' ) + ' ' + decodeEntities( author ) }</cite>
					) }

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
