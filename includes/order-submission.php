<?php
/**
 * Order Submission - Core submission logic for RRD API integration
 *
 * Handles:
 * - Payload generation from WooCommerce orders
 * - API submission orchestration
 * - Response handling and storage
 */

defined( 'ABSPATH' ) || exit;

/**
 * Submit order to RRD API
 *
 * @param WC_Order $order
 * @return array Success/failure result
 */
function rrd_submit_order_to_api( $order ) {
	$order_id = $order->get_id();

	try {
		// Prepare order for submission
		RRD_Order_Service::prepare_submission( $order );

		// Generate payload
		$payload = RRD_Payload_Builder::build_basic_order( $order );
		$payload_json = wp_json_encode( $payload );

		// Store request payload
		RRD_Order_Service::store_request_payload( $order, $payload_json );

		// Log request (with masked credentials)
		rrd_log( 'sending_request', array(
			'order_id'  => $order_id,
			'endpoint'  => rrd_get_api_endpoint(),
			'payload'   => $payload,
		), $order_id );

		// Submit to RRD API
		$api_response = RRD_API_Client::submit( $payload_json );
		$return_code = $api_response['return_code'];

		// Store response data
		RRD_Order_Service::store_response( $order, $api_response );

		// Handle response and update order state
		$is_success = RRD_Response_Handler::handle( $order, $api_response );

		$order->save();

		return array(
			'success' => $is_success,
			'message' => 'Submission processed',
			'code'    => $return_code,
		);
	} catch ( Exception $e ) {
		$order->update_meta_data( 'rrd_submission_status', 'failed' );
		$order->save();

		rrd_log( 'submission_error', array(
			'order_id' => $order_id,
			'error'    => $e->getMessage(),
		), $order_id );

		return array(
			'success' => false,
			'message' => $e->getMessage(),
		);
	}
}
