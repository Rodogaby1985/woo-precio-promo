<?php
/**
 * Plugin Name:       Woo Precio Promo
 * Plugin URI:        https://github.com/Rodogaby1985/woo-precio-promo
 * Description:       Muestra el precio financiado/de lista (base + porcentaje de incremento) en las páginas de productos y aplica un recargo al finalizar la compra cuando se utiliza una pasarela de pago distinta a la transferencia bancaria. Se puede configurar desde WooCommerce → Precio Promo en el panel de administración de WordPress..
 * Version:           1.1.0
 * Author:            MoobeUP
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
		esc_html__( 'Woo Precio Promo requiere que WooCommerce esté instalado y activo.', 'woo-precio-promo' ) .
		'</p></div>';
}
