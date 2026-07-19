<?php
/**
 * RRD Response Handler
 *
 * Responsible for processing API responses and updating order state accordingly.
 * Determines success/failure, updates status, adds order notes, and logs events.
 *
 * @package RRD_WooCommerce_Integration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RRD_Response_Handler
 *
 * Handles API response processing and order state management.
 */
class RRD_Response_Handler {

	/**
	 * Handle API response and update order state
	 *
	 * Determines success/failure based on return code, updates order meta, and logs the submission result.
	 * Note: Status is displayed in the RRD meta box widget, not added as order notes to avoid duplication.
	 *
	 * @param WC_Order $order       The WooCommerce order object.
	 * @param array    $api_response API response data from RRD_API_Client::submit().
	 *   - 'return_code' (int|string): API return code (200 for success, "Success"/"Fail" status, or error code)
	 *   - 'description' (string): API description text or error message
	 *   - 'response_body' (string): Full response body
	 *   - 'http_code' (int): HTTP status code
	 * @return bool True if successful (return_code === 200 or Status === "Success"), false otherwise.
	 */
	public static function handle( $order, $api_response ) {
		$order_id = $order->get_id();
		$return_code = $api_response['return_code'];
		$description = $api_response['description'];
		$response_body = $api_response['response_body'];

		// Determine success based on return code or status
		// Success if: numeric 200 OR string "Success"/"SUCCESS" (case-insensitive)
		$is_success = ( 200 === $return_code || strtoupper( (string) $return_code ) === 'SUCCESS' );

		if ( $is_success ) {
			// Handle success
			$order->update_meta_data( 'rrd_submission_status', 'success' );

			rrd_log( 'submission_success', array(
				'order_id'    => $order_id,
				'return_code' => $return_code,
			), $order_id );
		} else {
			// Handle failure
			$order->update_meta_data( 'rrd_submission_status', 'failed' );

			rrd_log( 'submission_failed', array(
				'order_id'    => $order_id,
				'return_code' => $return_code,
				'description' => $description,
				'response'    => $response_body,
			), $order_id );
		}

		return $is_success;
	}
}
