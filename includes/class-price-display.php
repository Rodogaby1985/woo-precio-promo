<?php
/**
 * Price display logic.
 *
 * Replaces the standard WooCommerce price HTML with a three-line block:
 *
 *   [financed price – smaller, gray]
 *   [base/transfer price – prominent, red]  con Transferencia
 *   [installments line – small, dark gray]
 *
 * @package WooPrecioPromo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPP_Price_Display
 */
class WPP_Price_Display {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'custom_price_html' ), 100, 2 );
	}

	/**
	 * Build the custom price HTML for a product.
	 *
	 * @param string     $price_html Original price HTML.
	 * @param WC_Product $product    Product object.
	 * @return string
	 */
	public static function custom_price_html( $price_html, $product ) {
		// Skip in wp-admin (non-AJAX) to avoid breaking order screens, etc.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $price_html;
		}

		// Only handle simple products and product variations.
		// For variable products the parent shows a price range; we leave that
		// untouched so WooCommerce can handle the "from $X" display naturally.
		// The filter runs again for the chosen variation after selection.
		if ( $product->is_type( 'variable' ) ) {
			return $price_html;
		}

		$base_price = (float) $product->get_price();
		if ( $base_price <= 0 ) {
			return $price_html;
		}

		$uplift          = (float) WPP_UPLIFT;
		$installments    = absint( WPP_INSTALLMENTS );
		$financed_price  = $base_price * ( 1 + $uplift );
		$installment_amt = ( $installments > 0 ) ? ( $financed_price / $installments ) : 0;

		ob_start();
		?>
		<div class="wpp-precio-wrapper">
			<div class="wpp-precio-financiado">
				<?php echo wc_price( $financed_price ); ?>
			</div>
			<div class="wpp-precio-transferencia">
				<?php echo wc_price( $base_price ); ?>
				<span class="wpp-transferencia-label"><?php esc_html_e( 'con Transferencia', 'woo-precio-promo' ); ?></span>
			</div>
			<?php if ( $installments > 0 && $installment_amt > 0 ) : ?>
			<div class="wpp-precio-cuotas">
				<?php
				printf(
					/* translators: 1: number of installments, 2: formatted installment amount */
					esc_html__( '%1$d cuotas sin inter&eacute;s de %2$s', 'woo-precio-promo' ),
					$installments,
					wc_price( $installment_amt )
				);
				?>
			</div>
			<?php endif; ?>
		</div>
		<style>
		.wpp-precio-wrapper { line-height: 1.4; }
		.wpp-precio-financiado { font-size: 0.85em; color: #9b9b9b; text-decoration: none; }
		.wpp-precio-financiado .woocommerce-Price-amount { font-size: inherit; color: inherit; }
		.wpp-precio-transferencia { font-size: 1.6em; color: #c0392b; font-weight: 700; }
		.wpp-precio-transferencia .woocommerce-Price-amount { font-size: inherit; color: inherit; }
		.wpp-transferencia-label { font-size: 0.6em; color: #c0392b; font-weight: 400; vertical-align: middle; margin-left: 4px; }
		.wpp-precio-cuotas { font-size: 0.85em; color: #555; margin-top: 2px; }
		.wpp-precio-cuotas .woocommerce-Price-amount { font-size: inherit; color: inherit; }
		</style>
		<?php
		return ob_get_clean();
	}
}
