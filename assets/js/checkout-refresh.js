/**
 * Woo Precio Promo – checkout refresh
 *
 * Triggers a WooCommerce checkout update whenever the customer changes
 * the payment method, so the financing surcharge is recalculated in real time.
 */
jQuery( function ( $ ) {
	'use strict';

	$( 'form.checkout' ).on( 'change', 'input[name="payment_method"]', function () {
		$( 'body' ).trigger( 'update_checkout' );
	} );
} );
