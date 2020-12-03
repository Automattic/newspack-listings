<?php
/**
 * Front-end render functions for the Curated List Container block.
 * This is a blank wrapper block that contains listing items.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Curated_List_Container_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Utils as Utils;

/**
 * Dynamic block registration.
 */
function register_block() {
	// Parent Curated List block attributes.
	$parent_block_json = json_decode(
		file_get_contents( dirname( __DIR__ ) . '/curated-list/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true
	);

	// Register Curated List Container block.
	register_block_type(
		'newspack-listings/list-container',
		[
			'attributes'      => $parent_block_json['attributes'],
			'render_callback' => __NAMESPACE__ . '\render_block',
		]
	);
}

/**
 * Block render callback.
 *
 * @param array  $attributes Block attributes.
 * @param string $inner_content InnerBlock content.
 */
function render_block( $attributes, $inner_content ) {
	$content = '';

	if ( $attributes['showSortUi'] ) {
		$is_amp        = Utils\is_amp();
		$rest_url      = rest_url( '/newspack-listings/v1/listings' );
		$block_id      = wp_unique_id();
		$query_options = $attributes['queryOptions'];

		// Unset sortBy and order options so they can be reset by sort UI.
		unset( $query_options['sortBy'] );
		unset( $query_options['order'] );

		// Only include the attributes we care about for individual listings.
		$request_attributes = Utils\get_request_attributes( $attributes );
		$sort_by_url        = $attributes['queryMode'] ?
			add_query_arg(
				[
					'attributes' => $request_attributes,
					'query'      => $query_options,
					'page'       => 1,
					'amp'        => $is_amp,
					'_fields'    => 'html',
				],
				$rest_url
			) :
			add_query_arg(
				[
					'attributes' => $request_attributes,
					'query'      => [ 'post__in' => $attributes['listingIds'] ],
					'page'       => 1,
					'per_page'   => 100,
					'amp'        => $is_amp,
					'_fields'    => 'html',
				],
				$rest_url
			);

		// Begin front-end output.
		ob_start();
		?>
		<form
			class="newspack-listings__sort-ui"

			<?php if ( $attributes['showSortUi'] ) : ?>
				data-url="<?php echo esc_url( $sort_by_url ); ?>"
			<?php endif; ?>
		>
			<section>
				<label class="newspack-listings__sort-ui-label" for="<?php echo esc_attr( 'newspack-listings__sort-by__' . $block_id ); ?>"><?php echo esc_html( __( 'Sort by:', 'newspack-listings' ) ); ?></label>
				<select
					class="newspack-listings__sort-select-control"
					id="<?php echo esc_attr( 'newspack-listings__sort-by__' . $block_id ); ?>"
				>
					<?php if ( ! $attributes['queryMode'] ) : ?>
						<option value="post__in" selected>
							<?php echo esc_html( __( 'Default', 'newspack-listings' ) ); ?>
						</option>
					<?php endif; ?>
					<?php if ( Core::NEWSPACK_LISTINGS_POST_TYPES['event'] === $attributes['queryOptions']['type'] ) : ?>
						<option
							value="event_date"
							<?php if ( $attributes['queryMode'] && 'event_date' === $attributes['queryOptions']['sortBy'] ) : ?>
								selected
							<?php endif; ?>
						>
							<?php echo esc_html( __( 'Event Date', 'newspack-listings' ) ); ?>
						</option>
					<?php endif; ?>
					<option
						value="date"
						<?php if ( $attributes['queryMode'] && 'date' === $attributes['queryOptions']['sortBy'] ) : ?>
							selected
						<?php endif; ?>
					>
						<?php echo esc_html( __( 'Publish Date', 'newspack-listings' ) ); ?>
					</option>
					<option
						value="title"
						<?php if ( $attributes['queryMode'] && 'title' === $attributes['queryOptions']['sortBy'] ) : ?>
							selected
						<?php endif; ?>
					>
						<?php echo esc_html( __( 'Title', 'newspack-listings' ) ); ?>
					</option>

					<?php if ( ! $attributes['queryMode'] || 'any' === $attributes['queryOptions']['type'] ) : ?>
						<option
							value="type"
							<?php if ( $attributes['queryMode'] && 'type' === $attributes['queryOptions']['sortBy'] ) : ?>
								selected
							<?php endif; ?>
						>
							<?php echo esc_html( __( 'Listing Type', 'newspack-listings' ) ); ?>
						</option>
					<?php endif; ?>

					<?php if ( $attributes['showAuthor'] ) : ?>
						<option
							value="author"
							<?php if ( $attributes['queryMode'] && 'author' === $attributes['queryOptions']['sortBy'] ) : ?>
								selected
							<?php endif; ?>
						>
							<?php echo esc_html( __( 'Author', 'newspack-listings' ) ); ?>
						</option>
					<?php endif; ?>
				</select>
			</section>

			<section>
				<label class="newspack-listings__sort-ui-label"><?php echo esc_html( __( 'Sort order:', 'newspack-listings' ) ); ?></label>

				<div>
					<input
						id="<?php echo esc_attr( 'newspack-listings__sort-asc__' . $block_id ); ?>"
						type="radio"
						name="newspack-listings__sort-order"
						value="ASC"

						<?php if ( ! $attributes['queryMode'] || 'ASC' === $attributes['queryOptions']['order'] ) : ?>
							checked
						<?php endif; ?>
						<?php if ( ! $attributes['queryMode'] ) : ?>
							disabled
						<?php endif; ?>
					/>
					<label class="newspack-listings__sort-ui-label-inner" for="<?php echo esc_attr( 'newspack-listings__sort-asc__' . $block_id ); ?>"><?php echo esc_html( __( 'Ascending', 'newspack-listings' ) ); ?></label>
				</div>

				<div>
					<input
						id="<?php echo esc_attr( 'newspack-listings__sort-desc__' . $block_id ); ?>"
						type="radio"
						name="newspack-listings__sort-order"
						value="DESC"

						<?php if ( $attributes['queryMode'] && 'DESC' === $attributes['queryOptions']['order'] ) : ?>
							checked
						<?php endif; ?>
						<?php if ( ! $attributes['queryMode'] ) : ?>
							disabled
						<?php endif; ?>
					/>
					<label class="newspack-listings__sort-ui-label-inner" for="<?php echo esc_attr( 'newspack-listings__sort-desc__' . $block_id ); ?>"><?php echo esc_html( __( 'Descending', 'newspack-listings' ) ); ?></label>
				</div>
			</section>
		</form>
		<?php
		$content .= ob_get_clean();
	}

	// Bail if there's no InnerBlock content to display.
	if ( $attributes['queryMode'] || empty( trim( $inner_content ) ) ) {
		return $content;
	}

	// Begin front-end output.
	ob_start();

	?>
	<ol class="newspack-listings__list-container">
		<?php echo wp_kses_post( $inner_content ); ?>
	</ol>
	<?php

	$content .= ob_get_clean();

	return $content;
}

register_block();
