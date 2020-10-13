<?php
/**
 * Template for a single listing item within a curated list.
 *
 * @global array $attributes Block attributes.
 * @package WordPress
 */

call_user_func(
	function( $data ) {
		$attributes = $data['attributes'];
		$post       = $data['post'];

		if ( empty( $attributes ) || empty( $post ) ) {
			return;
		}

		?>
	<li class="newspack-listings__listing">
	<article
		class="newspack-listings__listing-post"
		<?php
		if ( ! empty( $attributes['textColor'] ) ) {
			echo esc_attr( 'style="color:' . $attributes['textColor'] . ';"' );
		}
		?>
	>
		<?php if ( true === $attributes['showImage'] ) : ?>
			<?php
			$featured_image = get_the_post_thumbnail( $post->ID, 'large' );
			if ( ! empty( $featured_image ) ) :
				?>
				<figure class="newspack-listings__listing-featured-media">
					<a class="newspack-listings__listing-link" href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
						<?php echo wp_kses_post( $featured_image ); ?>
						<?php if ( true === $attributes['showCaption'] ) : ?>
						<figcaption class="newspack-listings__listing-caption">
							<?php echo wp_kses_post( get_the_post_thumbnail_caption( $post->ID ) ); ?>
						</figcaption>
						<?php endif; ?>
					</a>
				</figure>
			<?php endif; ?>
		<?php endif; ?>

		<div class="newspack-listings__listing-meta">
			<?php if ( true === $attributes['showCategory'] ) : ?>
			<div class="cat-links">
				<?php
				$categories = get_the_terms( $post->ID, Core::NEWSPACK_LISTINGS_CAT );
				if ( is_array( $categories ) ) :
					foreach ( $categories as $category ) :
						$term_url = get_term_link( $category->slug, Core::NEWSPACK_LISTINGS_CAT );

						if ( empty( $term_url ) ) {
							$term_url = '#';
						}
						?>
						<a href="<?php echo esc_url( $term_url ); ?>">
							<?php echo wp_kses_post( $category->name ); ?>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<a class="newspack-listings__listing-link" href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
				<h3 class="newspack-listings__listing-title"><?php echo wp_kses_post( $post->post_title ); ?></h3>
				<?php if ( true === $attributes['showAuthor'] ) : ?>
				<cite>
					<?php echo wp_kses_post( __( 'By', 'newspack-listings' ) . ' ' . get_the_author_meta( 'display_name', $post->post_author ) ); ?>
				</cite>
				<?php endif; ?>

				<?php
				if ( true === $attributes['showExcerpt'] ) {
					echo wp_kses_post( wpautop( get_the_excerpt( $post->ID ) ) );
				}
				?>
			</a>
		</div>
	</article>
	</li>
		<?php
	},
	$data // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
);
