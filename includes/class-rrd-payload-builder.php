<?php
/**
 * RRD Payload Builder
 *
 * Responsible for building BasicOrder and CustomArtOrder payloads
 * from WooCommerce order data.
 *
 * @package RRD_WooCommerce_Integration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RRD_Payload_Builder
 *
 * Handles conversion of WooCommerce orders to RRD API payload format.
 */
class RRD_Payload_Builder {

	/**
	 * Build BasicOrder payload from WooCommerce order
	 *
	 * Extracts product line items from the order, maps SKU/quantity/UOM,
	 * and constructs a complete BasicOrder payload for the RRD API.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 * @return array BasicOrder payload ready for JSON encoding.
	 */
	public static function build_basic_order( $order ) {
		// Build payload with real product line items from the order
		$line_items = array();
		$line_number = 1;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			// Skip if product doesn't exist
			if ( ! $product ) {
				continue;
			}

			// Get SKU, use MISSING_SKU if not set
			$sku = $product->get_sku();
			if ( empty( $sku ) ) {
				$sku = 'MISSING_SKU';
				rrd_log(
					'Warning: Line ' . $line_number . ' has missing SKU - product "' . $product->get_name() . '" will show as MISSING_SKU in payload',
					$order->get_id(),
					'payload_builder'
				);
			}

			// Get UOM from product meta, default to 'EA'
			$uom = $product->get_meta( 'rrd_uom' );
			if ( empty( $uom ) ) {
				$uom = 'EA';
			}

			// Build line item
			$line_items[] = array(
				'LineNumber'  => $line_number,
				'CustomerSKU' => $sku,
				'Quantity'    => $item->get_quantity(),
				'UOM'         => $uom,
			);

			$line_number++;
		}

		// Build complete payload
		$payload = array(
			'OrderType'        => 'BasicOrder',
			'ClientId'         => get_option( 'rrd_client_id', '' ),
			'PONumber'         => 'WC-' . $order->get_order_number(),
			'SalesOrderNumber' => 'WC-' . $order->get_order_number(),
			'Line'             => $line_items,
			'ShipToCode'       => 0,
			'ShipToName'       => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
			'ShipToAddress1'   => $order->get_shipping_address_1(),
			'ShipToCity'       => $order->get_shipping_city(),
			'ShipToState'      => $order->get_shipping_state(),
			'ShipToZip'        => $order->get_shipping_postcode(),
			'ShipToCountry'    => $order->get_shipping_country(),
			'ShipToPhone'      => $order->get_billing_phone(),
		);

		return $payload;
	}
}
