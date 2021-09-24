<?php
/**
 * Template for a self-serve listing purchase form.
 *
 * @global array $attributes Block attributes.
 * @package WordPress
 */

use \Newspack_Listings\Newspack_Listings_Core as Core;
use \Newspack_Listings\Utils as Utils;

call_user_func(
	function( $data ) {
		$attributes               = $data['attributes'];
		$allowed_listing_types    = $attributes['allowedSingleListingTypes'];
		$allow_subscription       = $attributes['allowSubscription'];
		$button_text              = $attributes['buttonText'];
		$single_description       = $attributes['singleDescription'];
		$subscription_description = $attributes['subscriptionDescription'];
		$client_id                = $attributes['clientId'];

		$class_names = [ 'newspack-listings__self-serve-form', 'wpbnbd', 'tiered' ];

		if ( ! $allow_subscription ) {
			$class_names[] = 'single-only';
		}

		if ( empty( $attributes ) ) {
			return;
		}
		?>
<div class="<?php echo esc_attr( implode( ' ', $class_names ) ); ?>">
	<form>
		<div class="frequencies">
			<div class="newspack-listings__form-tabs frequency">
				<input
					name="listing-purchase-type"
					class="newspack-listings__tab-input"
					id="listing-single-<?php echo esc_attr( $client_id ); ?>"
					type="radio"
					checked
					value="single"
				/>
				<label
					class="freq-label listing-single"
					for="listing-single-<?php echo esc_attr( $client_id ); ?>"
				>
					<?php echo esc_html( __( 'Single Listing' ) ); ?>
				</label>
				<div class="input-container listing-details">
					<p><?php echo wp_kses_post( $single_description ); ?></p>
					<h3><?php echo esc_html( __( 'Listing Details', 'newspack-listings' ) ); ?></h3>
					<label for="listing-type-<?php echo esc_attr( $client_id ); ?>">
						<?php echo esc_html( __( 'What type of listing would you like to purchase?', 'newspack-listings' ) ); ?>
					</label>
					<select
						id="listing-type-<?php echo esc_attr( $client_id ); ?>"
						name="listing-single-type"
					>
						<?php
						array_map(
							function ( $listing_type ) {
								?>
								<option value="<?php echo esc_attr( $listing_type['slug'] ); ?>">
									<?php echo esc_html( $listing_type['name'] ); ?>
								</option>
								<?php
							},
							$allowed_listing_types
						);
		?>
					</select>
					<label for="listing-single-upgrade-<?php echo esc_attr( $client_id ); ?>">
						<?php echo esc_html( __( 'Upgrade to a featured listing:', 'newspack-listings' ) ); ?>
					</label>
					<input
						type="checkbox"
						id="listing-single-upgrade-<?php echo esc_attr( $client_id ); ?>"
						name="listing-featured-upgrade"
					/>
					<p class="newspack-listings__help">
						<?php
						echo esc_html(
							__(
								'Featured listings appear first in lists, directory pages and search results.',
								'newspack-listings'
							)
						);
						?>
					</p>
				</div>
			</div>
			<?php if ( $allow_subscription ) : ?>
				<div class="newspack-listings__form-tabs frequency">
					<input
						name="listing-purchase-type"
						class="newspack-listings__tab-input"
						id="listing-subscription-<?php echo esc_attr( $client_id ); ?>"
						type="radio"
						value="subscription"
					/>
					<label
						class="freq-label listing-subscription"
						for="listing-subscription-<?php echo esc_attr( $client_id ); ?>"
					>
						<?php echo esc_html( __( 'Listing Subscription' ) ); ?>
					</label>
					<div class="input-container listing-details">
						<p><?php echo wp_kses_post( $subscription_description ); ?></p>
						<h3><?php echo esc_html( __( 'Listing Details', 'newspack-listings' ) ); ?></h3>
						<label for="listing-subscription-upgrade-<?php echo esc_attr( $client_id ); ?>">
							<?php echo esc_html( __( 'Upgrade to a premium subscription:', 'newspack-listings' ) ); ?>
						</label>
						<input
							type="checkbox"
							id="listing-subscription-upgrade-<?php echo esc_attr( $client_id ); ?>"
							name="listing-premium-upgrade"
						/>
						<p class="newspack-listings__help">
							<?php
							echo esc_html(
								__(
									'A premium subscription lets you publish up to five listings related to your organization per month.',
									'newspack-listings'
								)
							);
							?>
						</p>
					</div>
				</div>
			<?php endif; ?>
			<div class="shared-inputs listing-details">
				<label for="listing-title-<?php echo esc_attr( $client_id ); ?>">
					<?php echo esc_html( __( 'Enter a name for your listing:', 'newspack-listings' ) ); ?>
				</label>
				<input
					class="listing-subscription"
					type="text"
					id="listing-title-<?php echo esc_attr( $client_id ); ?>"
					name="listing-title"
					value=""
					placeholder="<?php echo esc_attr( __( 'Listing Title' ) ); ?>"
					required
				/>
			</div>
		</div>
		<button type="submit">
			<?php echo esc_html( $button_text ); ?>
		</button>
	</form>
</div>
		<?php
	},
	$data // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
);
