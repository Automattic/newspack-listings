/* eslint-disable jsx-a11y/anchor-is-valid */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { Fragment, RawHTML } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';

export const Listing = ( { attributes, error, post } ) => {
	// Parent Curated List block attributes.
	const { showAuthor, showCategory, showExcerpt, showImage, showCaption } = attributes;

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
									<a href="#">{ decodeEntities( category.name ) }</a>
									{ index + 1 < post.category.length && ', ' }
								</Fragment>
							) ) }
						</div>
					) }
					<h3 className="newspack-listings__listing-title">{ decodeEntities( post.title ) }</h3>
					{ showAuthor && post.author && (
						<cite>{ __( 'By', 'newpack-listings' ) + ' ' + decodeEntities( post.author ) }</cite>
					) }
					{ showExcerpt && post.excerpt && <RawHTML>{ post.excerpt }</RawHTML> }
				</div>
			) }
		</div>
	);
};
