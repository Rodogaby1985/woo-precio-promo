# Woo Precio Promo

A lightweight WooCommerce mini-plugin for stores whose **base product price comes from an external ERP / management system** (the "cash / bank-transfer price"). The plugin visually displays a higher financed/list price and automatically applies a financing surcharge at checkout when any payment gateway **other than bank transfer** is chosen.

---

## What it does

### Storefront price display

On every product page and in the catalog grid, the standard price block is replaced with a three-line layout:

```
$ 31.266,54          ← financed / list price (base × 1.36), small gray text
$ 22.990,10 con Transferencia   ← base price in large red, labeled
18 cuotas sin interés de $ 1.736,00   ← installment breakdown, small gray
```

### Checkout surcharge

| Payment method chosen | Surcharge |
|-----------------------|-----------|
| Bank transfer (`bacs`) | None – customer pays the base price |
| Any other gateway | +36 % of the cart subtotal is added as a fee line item |

The surcharge is recalculated live whenever the customer switches payment methods, keeping the order total consistent with the displayed financed price.

---

## Installation

1. Download or clone this repository.
2. Upload the entire `woo-precio-promo` folder to your site's `wp-content/plugins/` directory.
3. In the WordPress admin, go to **Plugins → Installed Plugins** and activate **Woo Precio Promo**.
4. No further configuration is required – the plugin works immediately with WooCommerce's default bank-transfer gateway (`bacs`).

---

## Configuración

### Pantalla de ajustes en el admin (recomendada)

Desde la versión 1.1.0 el plugin incluye una pantalla de configuración en el back-end de WordPress, con interfaz y textos por defecto en español.

1. En el admin de WordPress, ingresá a **WooCommerce → Precio Promo**.
2. Ajustá los campos según tu tienda y hacé clic en **Guardar ajustes**.

| Campo | Valor por defecto | Descripción |
|---|---|---|
| **Activar plugin** | `Sí` | Si está desactivado, el plugin no modifica precios ni agrega recargos en checkout. |
| **Porcentaje de recargo (%)** | `36` | Porcentaje que se suma al precio base (ejemplo: `36` para 36 %). |
| **Cantidad de cuotas** | `18` | Cuotas mostradas en la línea de cuotas. Usá `0` para ocultarla. |
| **ID de medio de pago sin recargo** | `bacs` | ID de WooCommerce que se toma como transferencia sin recargo. |
| **Texto del precio por transferencia** | `con Transferencia` | Texto que se agrega al precio base en producto/catálogo. |
| **Plantilla de línea de cuotas** | `{count} cuotas sin interés de {amount}` | Plantilla para la línea de cuotas; `{count}` y `{amount}` se reemplazan en tiempo de ejecución. |
| **Texto del recargo en checkout** | `Recargo por financiación` | Texto mostrado para la línea de recargo en el total del checkout. |

### Advanced: PHP constants (backward compatibility)

For programmatic configuration, you can still define constants in `wp-config.php`. Constants take priority over the admin settings, which is useful in multi-environment setups (staging vs. production).

| Constant | Default | Description |
|---|---|---|
| `WPP_ENABLED` | `true` | Enables/disables plugin behavior globally (`false` keeps WooCommerce untouched). |
| `WPP_UPLIFT` | `0.36` | Fractional uplift applied to the base price (0.36 = 36 %). |
| `WPP_INSTALLMENTS` | `18` | Number of equal installments. Set to `0` to hide the line. |
| `WPP_TRANSFER_GATEWAY` | `'bacs'` | WooCommerce gateway ID that is treated as the no-surcharge "transfer" method. |

**Example** – change the uplift to 40 % and use 12 installments:

```php
// wp-config.php
define( 'WPP_UPLIFT', 0.40 );
define( 'WPP_INSTALLMENTS', 12 );
```

To find the correct gateway ID for a third-party payment plugin, check the value of `chosen_payment_method` in the WooCommerce session or inspect the `value` attribute of the payment-method radio buttons on the checkout page.

---

## How the surcharge is calculated

```
financed_price  = base_price × (1 + uplift)
surcharge       = cart_subtotal × uplift
```

Because the cart subtotal is the sum of base prices, adding `subtotal × uplift` makes the grand total equal `subtotal × (1 + uplift)`, which exactly matches the financed prices shown on the product pages.

> **Note:** The surcharge fee is currently added as **non-taxable**. If your store applies taxes to fees, open `includes/class-checkout-fee.php` and change the third argument of `$cart->add_fee()` from `false` to `true`.

---

## Assumptions & limitations

### Taxes
The plugin assumes prices are managed **tax-exclusive** (the ERP sends net prices and WooCommerce is configured to display prices excluding tax, or taxes are zero-rated). If your store uses tax-inclusive pricing, review how WooCommerce calculates `get_subtotal()` and `get_price()` in your context.

### Variable products
Variable product parents are now supported. For catalog/single pre-selection states, the plugin uses the **minimum variation price** as the base for the three-line promo block. After a variation is selected, WooCommerce variation pricing still updates normally and the promo display is recalculated from that selected variation price.

### External / synced prices
The plugin reads `$product->get_price()` – whatever WooCommerce currently stores as the active price. If your ERP sync writes to the regular price field and leaves the sale price empty, the plugin will work correctly. If your sync uses the sale-price field as the active price, `get_price()` will still return the correct value (WooCommerce returns the sale price when one is set).

### Multiple surcharges
The surcharge is calculated on `$cart->get_subtotal()` (sum of product line items, before any existing fees). Existing cart fees or coupons are not factored in. For complex stores with multiple discounts or fees, review the calculation logic in `includes/class-checkout-fee.php`.

### Coupon compatibility
Standard WooCommerce coupons reduce the subtotal before `woocommerce_cart_calculate_fees` runs, so the surcharge is automatically applied to the discounted subtotal. This is usually the desired behaviour.

### Shipping
Shipping costs are not included in the surcharge base. Only product line items are uplifted.

---

## File structure

```
woo-precio-promo/
├── woo-precio-promo.php          # Plugin bootstrap & constants
├── includes/
│   ├── class-settings.php        # Admin settings page & WPP_Settings helper
│   ├── class-price-display.php   # Price HTML filter + loop/single fallback hooks
│   └── class-checkout-fee.php    # Checkout surcharge + JS enqueue
├── assets/
│   └── js/
│       └── checkout-refresh.js   # Triggers checkout update on gateway change
└── README.md
```

---

## Frequently asked questions

**Can I use a different gateway as the "free" transfer method?**  
Yes – define `WPP_TRANSFER_GATEWAY` with the ID of your gateway (e.g. `'pagonube'`, `'woo-mercado-pago-basic'`).

**What if I want multiple gateways to be surcharge-free?**  
The current implementation supports a single exempt gateway. To exempt several gateways, modify the condition in `WPP_Checkout_Fee::maybe_add_surcharge()`:

```php
$exempt = array( 'bacs', 'cheque', 'cod' );
if ( in_array( $chosen_gateway, $exempt, true ) ) {
    return;
}
```

**Can I style the price block differently?**  
The inline `<style>` block in `class-price-display.php` targets BEM-style classes (`.wpp-precio-financiado`, `.wpp-precio-transferencia`, `.wpp-precio-cuotas`). Override these in your theme's CSS.

---

## License

GPL-2.0-or-later. See [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).
