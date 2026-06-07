<?php
/**
 * Checkout fee / surcharge logic.
 *
 * When the customer chooses a payment method other than the configured
 * "transfer" gateway (default: bacs), a surcharge equal to WPP_UPLIFT of
 * the cart subtotal is added so the final amount matches the financed/list
 * price displayed on product pages.
 *
 * @package WooPrecioPromo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPP_Checkout_Fee
 */
class WPP_Checkout_Fee {

	/**
	 * Register hooks.
	 */
	public static function init() {
		// Apply the fee when cart totals are calculated.
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'maybe_add_surcharge' ) );

		// Enqueue the JS that triggers a checkout update when the payment
		// method radio button changes.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Conditionally add a financing surcharge to the cart.
	 *
	 * @param WC_Cart $cart Current cart instance.
	 */
	public static function maybe_add_surcharge( $cart ) {
		// Bail in back-end non-AJAX contexts (e.g. admin order recalculation).
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! WPP_Settings::get( 'enabled' ) ) {
			return;
		}

		if ( ! WC()->session ) {
			return;
		}

		$chosen_gateway = WC()->session->get( 'chosen_payment_method' );

		// If no gateway is chosen yet, or it is the transfer gateway, do nothing.
		if ( empty( $chosen_gateway ) || $chosen_gateway === WPP_Settings::get( 'transfer_gateway' ) ) {
			return;
		}

		$uplift   = WPP_Settings::get( 'uplift' );
		$subtotal = $cart->get_subtotal();

		if ( $subtotal <= 0 ) {
			return;
		}

		$surcharge = $subtotal * $uplift;

		$cart->add_fee(
			/* translators: displayed as a line item in the cart/checkout totals */
			WPP_Settings::get( 'fee_label' ),
			$surcharge,
			false // taxable – set to true if surcharges should carry tax in your store
		);
	}

	/**
	 * Enqueue the front-end script on checkout pages.
	 */
	public static function enqueue_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		wp_enqueue_script(
			'wpp-checkout-refresh',
			plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/checkout-refresh.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}
}
