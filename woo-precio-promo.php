<?php
/**
 * Plugin Name:       Woo Precio Promo
 * Plugin URI:        https://github.com/Rodogaby1985/woo-precio-promo
 * Description:       Displays a financed/list price (base + uplift %) on product pages and applies a surcharge at checkout when a payment gateway other than bank transfer is used.
 * Version:           1.0.0
 * Author:            Rodogaby1985
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-precio-promo
 * Requires Plugins:  woocommerce
 *
 * @package WooPrecioPromo
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Configuration constants – override these in wp-config.php if needed.
// ---------------------------------------------------------------------------

/**
 * Fractional uplift applied on top of the base (transfer) price.
 * 0.36 = 36 %.
 */
if ( ! defined( 'WPP_UPLIFT' ) ) {
	define( 'WPP_UPLIFT', 0.36 );
}

/**
 * Number of equal installments shown in the "cuotas" line.
 */
if ( ! defined( 'WPP_INSTALLMENTS' ) ) {
	define( 'WPP_INSTALLMENTS', 18 );
}

/**
 * Payment gateway ID that is treated as the "cash / transfer" method.
 * No surcharge is added when this gateway is chosen.
 */
if ( ! defined( 'WPP_TRANSFER_GATEWAY' ) ) {
	define( 'WPP_TRANSFER_GATEWAY', 'bacs' );
}

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

add_action( 'plugins_loaded', 'wpp_init' );

/**
 * Load the plugin only when WooCommerce is active.
 */
function wpp_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wpp_missing_wc_notice' );
		return;
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-price-display.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-checkout-fee.php';

	WPP_Price_Display::init();
	WPP_Checkout_Fee::init();
}

/**
 * Admin notice when WooCommerce is not active.
 */
function wpp_missing_wc_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'Woo Precio Promo requires WooCommerce to be installed and active.', 'woo-precio-promo' ) .
		'</p></div>';
}
