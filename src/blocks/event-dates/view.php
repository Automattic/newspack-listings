<?php
/**
 * Front-end render functions for the Event Dates block.
 *
 * @package Newspack_Listings
 */

namespace Newspack_Listings\Event_Dates_Block;

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Utils as Utils;

/**
 * Dynamic block registration.
 */
function register_block() {
	// Block attributes.
	$block_json = json_decode(
		file_get_contents( __DIR__ . '/block.json' ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		true
	);

	// Register Evend Dates block.
	register_block_type(
		$block_json['name'],
		[
			'attributes'      => $block_json['attributes'],
			'render_callback' => __NAMESPACE__ . '\render_block',
		]
	);
}

/**
 * Are the given dates the same calendar day?
 *
 * @param DateTime $start_date Start date class.
 * @param DateTime $end_date End date class.
 * @return boolean Whether the two dates are the same day.
 */
function is_same_day( $start_date, $end_date ) {
	return $start_date->format( 'F j, Y' ) === $end_date->format( 'F j, Y' );
}

/**
 * Are the given dates in the same calendar month?
 *
 * @param DateTime $start_date Start date class.
 * @param DateTime $end_date End date class.
 * @return boolean Whether the two dates are in the same month.
 */
function is_same_month( $start_date, $end_date ) {
	return $start_date->format( 'F, Y' ) === $end_date->format( 'F, Y' );
}

/**
 * Are the given dates in the same calendar year?
 *
 * @param DateTime $start_date Start date class.
 * @param DateTime $end_date End date class.
 * @return boolean Whether the two dates are in the same year.
 */
function is_same_year( $start_date, $end_date ) {
	return $start_date->format( 'Y' ) === $end_date->format( 'Y' );
}

/**
 * Given a YYYY-MM-DDTHH:MM:SS date/time string, get only the date.
 *
 * @param string $date_string Date/time string in YYYY-MM-DDTHH:MM:SS format.
 * @return string The same date string, but without the timestamp.
 */
function strip_time( $date_string ) {
	return explode( 'T', $date_string )[0];
}

/**
 * Block render callback.
 *
 * @param array $attributes Block attributes.
 * @return string $content content.
 */
function render_block( $attributes ) {
	// Bail if there's no start date to display.
	if ( empty( $attributes['startDate'] ) ) {
		return '';
	}

	$date_format   = 'F j, Y';
	$time_format   = get_option( 'time_format', 'g:i A' );
	$is_date_range = ! empty( $attributes['endDate'] ) && ! empty( $attributes['showEnd'] );
	$start_date    = new \DateTime( $attributes['startDate'] );
	$end_date      = new \DateTime( $attributes['endDate'] );
	$show_time     = $attributes['showTime'];
	$is_same_day   = is_same_day( $start_date, $end_date );

	$start_date_format = $date_format;
	$end_date_format   = $date_format;

	if ( $is_date_range && ! $show_time ) {
		if ( is_same_year( $start_date, $end_date ) && empty( $is_same_day ) ) {
			$start_date_format = 'F j';
		}

		if ( is_same_month( $start_date, $end_date ) ) {
			$end_date_format = 'j, Y';
		}
	}

	$the_start_date = $start_date->format( $start_date_format );
	$the_end_date   = $end_date->format( $end_date_format );

	// Begin front-end output.
	ob_start();
	?>
	<div class="newspack-listings__event-dates">
		<time
			class="newspack-listings__event-date"
			datetime="<?php echo esc_attr( $show_time ? $attributes['startDate'] : strip_time( $attributes['startDate'] ) ); ?>"
		>
			<?php echo esc_html( $the_start_date ); ?>

			<?php if ( $show_time ) : ?>
				<span class="newspack-listings__event-date-time">
					<?php echo esc_html( $start_date->format( $time_format ) ); ?>
				</span>
			<?php endif; ?>
		</time>
		<?php if ( $is_date_range && empty( $is_same_day ) ) : ?>
			<?php if ( empty( $is_same_day ) || $show_time ) : ?>
				<span class="newspack-listings__event-time">
					<?php echo esc_html( _x( '–', 'Date separator', 'newspack-listings' ) ); ?>
				</span>
			<?php endif; ?>
			<time
				class="newspack-listings__event-date"
				datetime="<?php echo esc_attr( $show_time ? $attributes['endDate'] : strip_time( $attributes['endDate'] ) ); ?>"
			>
				<?php
				if ( empty( $is_same_day ) ) {
					echo esc_html( $the_end_date );
				}
				?>

				<?php if ( $show_time ) : ?>
					<span class="newspack-listings__event-time">
						<?php echo esc_html( $end_date->format( $time_format ) ); ?>
					</span>
				<?php endif; ?>
			</time>
		<?php endif; ?>
	</div>
	<?php

	$content = ob_get_clean();

	return $content;
}

register_block();
