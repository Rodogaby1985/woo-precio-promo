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
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'custom_price_html' ), 9999, 2 );
		add_action( 'wp', array( __CLASS__, 'register_theme_compat_hooks' ) );
	}

	/**
	 * Register fallback price hooks for themes that bypass the default template output.
	 *
	 * @return void
	 */
	public static function register_theme_compat_hooks() {
		if ( ! WPP_Settings::get( 'enabled' ) ) {
			return;
		}

		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'render_loop_price' ), 10 );

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_single_price' ), 10 );
	}

	/**
	 * Render custom price block in product loops.
	 *
	 * @return void
	 */
	public static function render_loop_price() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		echo self::build_custom_price_html( $product );
	}

	/**
	 * Render custom price block in single product summary.
	 *
	 * @return void
	 */
	public static function render_single_price() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		echo self::build_custom_price_html( $product );
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

		if ( ! WPP_Settings::get( 'enabled' ) ) {
			return $price_html;
		}

		$custom_price_html = self::build_custom_price_html( $product );
		if ( '' === $custom_price_html ) {
			return $price_html;
		}

		return $custom_price_html;
	}

	/**
	 * Build promo price block for a product.
	 *
	 * @param WC_Product $product Product object.
	 * @return string
	 */
	private static function build_custom_price_html( $product ) {
		$base_price = self::get_base_price_for_display( $product );
		if ( $base_price <= 0 ) {
			return '';
		}

		$uplift          = WPP_Settings::get( 'uplift' );
		$installments    = WPP_Settings::get( 'installments' );
		$financed_price  = $base_price * ( 1 + $uplift );
		$installment_amt = ( $installments > 0 ) ? ( $financed_price / $installments ) : 0;

		$transfer_label       = WPP_Settings::get( 'transfer_label' );
		$installment_template = WPP_Settings::get( 'installment_template' );

		ob_start();
		?>
		<div class="wpp-precio-wrapper">
			<div class="wpp-precio-financiado">
				<?php echo wc_price( $financed_price ); ?>
			</div>
			<div class="wpp-precio-transferencia">
				<?php echo wc_price( $base_price ); ?>
				<span class="wpp-transferencia-label"><?php echo esc_html( $transfer_label ); ?></span>
			</div>
			<?php if ( $installments > 0 && $installment_amt > 0 ) : ?>
			<div class="wpp-precio-cuotas">
				<?php
				echo wp_kses_post(
					str_replace(
						array( '{count}', '{amount}' ),
						array( $installments, wc_price( $installment_amt ) ),
						$installment_template
					)
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

	/**
	 * Resolve base price used for promo display.
	 *
	 * For variable parents we use the minimum variation price so catalog/single
	 * views can still show promo data before a variation is selected.
	 *
	 * @param WC_Product $product Product object.
	 * @return float
	 */
	private static function get_base_price_for_display( $product ) {
		if ( $product->is_type( 'variable' ) ) {
			return (float) $product->get_variation_price( 'min', false );
		}

		return (float) $product->get_price();
	}
}
