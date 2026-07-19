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
	 * Converts WooCommerce order data to RRD CustomArt API format.
	 * Wraps all data in required "Header" object structure.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 * @return array BasicOrder payload ready for JSON encoding.
	 */
	public static function build_basic_order( $order ) {
		// Build line items array
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

			// Get File_Name and File_Checksum from product meta, use defaults if not set
			$file_name = $product->get_meta( 'rrd_file_name' );
			if ( empty( $file_name ) ) {
				$file_name = $product->get_name();
			}

			$file_checksum = $product->get_meta( 'rrd_file_checksum' );
			if ( empty( $file_checksum ) ) {
				$file_checksum = '';
			}

			// Build line item according to RRD spec
			$line_items[] = array(
				'Line_Number'  => $line_number,
				'CustomerSKU'  => $sku,
				'Quantity'     => $item->get_quantity(),
				'UOM'          => $uom,
				'File_Name'    => $file_name,
				'File_Checksum' => $file_checksum,
			);

			$line_number++;
		}

		// Build ShipTo object with nested structure
		$ship_to_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
		// Fallback to billing name if shipping name is empty
		if ( empty( trim( $ship_to_name ) ) ) {
			$ship_to_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		}

		// Build address fields with fallback to billing if shipping is empty
		$ship_to_address1 = $order->get_shipping_address_1();
		if ( empty( trim( $ship_to_address1 ) ) ) {
			$ship_to_address1 = $order->get_billing_address_1();
		}

		$ship_to_address2 = $order->get_shipping_address_2();
		if ( empty( trim( $ship_to_address2 ) ) ) {
			$ship_to_address2 = $order->get_billing_address_2();
		}

		$ship_to_city = $order->get_shipping_city();
		if ( empty( trim( $ship_to_city ) ) ) {
			$ship_to_city = $order->get_billing_city();
		}

		$ship_to_state = $order->get_shipping_state();
		if ( empty( trim( $ship_to_state ) ) ) {
			$ship_to_state = $order->get_billing_state();
		}

		$ship_to_zip = $order->get_shipping_postcode();
		if ( empty( trim( $ship_to_zip ) ) ) {
			$ship_to_zip = $order->get_billing_postcode();
		}

		$ship_to_country = $order->get_shipping_country();
		if ( empty( trim( $ship_to_country ) ) ) {
			$ship_to_country = $order->get_billing_country();
		}

		$ship_to = array(
			'RRD_ShipTo_Code' => '0',
			'ShipTo_Name'      => $ship_to_name,
			'ShipTo_Address1'  => $ship_to_address1,
			'ShipTo_Address2'  => $ship_to_address2 ? $ship_to_address2 : '',
			'ShipTo_City'      => $ship_to_city,
			'ShipTo_State'     => $ship_to_state,
			'ShipTo_Zip'       => $ship_to_zip,
			'ShipTo_Country'   => $ship_to_country,
			'ShipTo_Phone'     => $order->get_billing_phone(),
		);

		// Build Header object
		$header = array(
			'OrderType'           => 'BasicOrder',
			'ClientId'            => get_option( 'rrd_client_id', '' ),
			'PO_Number'           => 'WC-' . $order->get_order_number(),
			'SalesOrderNumber'    => 'WC-' . $order->get_order_number(),
			'OrderNote'           => '',
			'ShipTo'              => $ship_to,
			'EXTCustomerReference' => array(),
			'Line'                => $line_items,
		);

		// Build complete payload with Header wrapper
		$payload = array(
			'Header' => $header,
		);

		return $payload;
	}
}
