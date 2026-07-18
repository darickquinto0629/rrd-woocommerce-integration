<?php
/**
 * RRD Order Service
 *
 * Responsible for order state management and data persistence.
 * Handles order meta updates, submission tracking, and payload storage.
 *
 * @package RRD_WooCommerce_Integration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RRD_Order_Service
 *
 * Manages order state and submission data persistence.
 */
class RRD_Order_Service {

	/**
	 * Prepare order for submission
	 *
	 * Sets initial submission status to 'pending' and increments submission count.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 */
	public static function prepare_submission( $order ) {
		$order->update_meta_data( 'rrd_submission_status', 'pending' );
		$order->update_meta_data( 'rrd_submit_count', (int) $order->get_meta( 'rrd_submit_count' ) + 1 );
		$order->save();
	}

	/**
	 * Store request payload
	 *
	 * Stores the JSON payload that was sent to the API for audit trail.
	 *
	 * @param WC_Order $order         The WooCommerce order object.
	 * @param string   $payload_json  The JSON payload string.
	 */
	public static function store_request_payload( $order, $payload_json ) {
		$order->update_meta_data( 'rrd_last_request_payload', $payload_json );
		$order->save();
	}

	/**
	 * Store API response data
	 *
	 * Stores the complete API response including response body, return code,
	 * description, and submission timestamp.
	 *
	 * @param WC_Order $order         The WooCommerce order object.
	 * @param array    $api_response  API response data from RRD_API_Client::submit().
	 *   - 'return_code' (int): API return code
	 *   - 'description' (string): API description text
	 *   - 'response_body' (string): Full response body
	 *   - 'http_code' (int): HTTP status code
	 */
	public static function store_response( $order, $api_response ) {
		$response_body = $api_response['response_body'];
		$return_code = $api_response['return_code'];
		$description = $api_response['description'];

		$order->update_meta_data( 'rrd_last_response_body', $response_body );
		$order->update_meta_data( 'rrd_last_submitted_at', current_time( 'mysql' ) );
		$order->update_meta_data( 'rrd_return_code', $return_code );
		$order->update_meta_data( 'rrd_description', $description );
		$order->save();
	}
}
