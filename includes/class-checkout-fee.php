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
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'maybe_add_surcharge' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'woocommerce_cart_item_price', array( __CLASS__, 'append_transfer_price_note_to_price' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'append_transfer_price_note_to_subtotal' ), 20, 3 );
		add_filter( 'woocommerce_widget_cart_item_quantity', array( __CLASS__, 'append_transfer_price_note_to_mini_cart' ), 20, 3 );
		add_action( 'woocommerce_review_order_after_cart_contents', array( __CLASS__, 'render_checkout_transfer_rows' ) );
	}

	/**
	 * Conditionally add a financing surcharge to the cart.
	 *
	 * @param WC_Cart $cart Current cart instance.
	 */
	public static function maybe_add_surcharge( $cart ) {
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
			WPP_Settings::get( 'fee_label' ),
			$surcharge,
			false
		);
	}

	/**
	 * Add transfer-price note below item unit price in cart.
	 *
	 * @param string $price_html Current item price HTML.
	 * @param array  $cart_item  Cart item data.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function append_transfer_price_note_to_price( $price_html, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product instanceof WC_Product ) {
			return $price_html;
		}

		return $price_html . self::get_transfer_note_html( $product, 1 );
	}

	/**
	 * Add transfer-price note below line subtotal in cart.
	 *
	 * @param string $subtotal_html Current subtotal HTML.
	 * @param array  $cart_item Cart item data.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function append_transfer_price_note_to_subtotal( $subtotal_html, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		$product  = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		$quantity = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;
		if ( ! $product instanceof WC_Product ) {
			return $subtotal_html;
		}

		return $subtotal_html . self::get_transfer_note_html( $product, $quantity );
	}

	/**
	 * Add transfer-price note to mini-cart line.
	 *
	 * @param string $html Existing mini-cart quantity/price HTML.
	 * @param array  $cart_item Cart item data.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public static function append_transfer_price_note_to_mini_cart( $html, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		$product  = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		$quantity = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;
		if ( ! $product instanceof WC_Product ) {
			return $html;
		}

		return $html . self::get_transfer_note_html( $product, $quantity );
	}

	/**
	 * Render transfer-price note rows inside checkout order review.
	 *
	 * @return void
	 */
	public static function render_checkout_transfer_rows() {
		if ( ! WPP_Settings::get( 'enabled' ) || ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$product  = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
			$quantity = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			?>
			<tr class="wpp-checkout-transfer-note" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
				<td colspan="2">
					<?php echo self::get_transfer_note_html( $product, $quantity ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Build red transfer-price note HTML.
	 *
	 * @param WC_Product $product Product instance.
	 * @param int        $quantity Quantity multiplier.
	 * @return string
	 */
	private static function get_transfer_note_html( $product, $quantity ) {
		if ( ! WPP_Settings::get( 'enabled' ) ) {
			return '';
		}

		$base_price = (float) $product->get_price();
		if ( $product->is_type( 'variable' ) ) {
			$base_price = (float) $product->get_variation_price( 'min', false );
		}

		if ( $base_price <= 0 ) {
			return '';
		}

		$total_transfer_price = $base_price * max( 1, (int) $quantity );

		ob_start();
		?>
		<div class="wpp-transfer-note" style="margin-top:4px; line-height:1.3; color:#c0392b; font-size:0.92em; font-weight:700;">
			<div class="wpp-transfer-note-price"><?php echo wp_kses_post( wc_price( $total_transfer_price ) ); ?></div>
			<div class="wpp-transfer-note-label"><?php echo esc_html__( 'Precio por pago con transferencia', 'woo-precio-promo' ); ?></div>
		</div>
		<?php
		return ob_get_clean();
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
