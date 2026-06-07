<?php
/**
 * Plugin Name:       Woo Precio Promo
 * Plugin URI:        https://github.com/Rodogaby1985/woo-precio-promo
 * Description:       Displays a financed/list price (base + uplift %) on product pages and applies a surcharge at checkout when a payment gateway other than bank transfer is used. Configurable from WooCommerce → Precio Promo in the WordPress admin.
 * Version:           1.1.0
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

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-settings.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-price-display.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-checkout-fee.php';

	WPP_Settings::init();
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
