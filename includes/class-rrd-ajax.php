<?php
/**
 * RRD AJAX Handler
 *
 * Responsible for handling AJAX requests from the admin interface.
 * Validates requests, processes actions, and returns JSON responses.
 *
 * @package RRD_WooCommerce_Integration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RRD_AJAX
 *
 * Handles AJAX endpoint processing and validation.
 */
class RRD_AJAX {

	/**
	 * Initialize AJAX handlers
	 *
	 * Registers all AJAX action endpoints.
	 */
	public static function init() {
		add_action( 'wp_ajax_rrd_preview_payload', array( __CLASS__, 'preview_payload' ) );
		add_action( 'wp_ajax_rrd_submit_order', array( __CLASS__, 'submit_order' ) );
	}

	/**
	 * Validate AJAX request (nonce, permissions, order)
	 *
	 * @param string $nonce_key POST nonce key
	 * @return WC_Order|WP_Error Order object or error
	 */
	private static function validate_request( $nonce_key ) {
		if ( ! check_ajax_referer( $nonce_key, 'nonce', false ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Security check failed', 'rrd-woocommerce-integration' ) );
		}

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return new WP_Error( 'permission_denied', __( 'Permission denied', 'rrd-woocommerce-integration' ) );
		}

		$order_id = intval( $_POST['order_id'] ?? 0 );
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error( 'order_not_found', __( 'Order not found', 'rrd-woocommerce-integration' ) );
		}

		return $order;
	}

	/**
	 * AJAX handler: Preview payload
	 *
	 * Generates and returns the payload that would be sent to RRD.
	 */
	public static function preview_payload() {
		$order = self::validate_request( 'rrd_preview_nonce' );

		if ( is_wp_error( $order ) ) {
			wp_send_json_error( array( 'message' => $order->get_error_message() ) );
		}

		$payload = RRD_Payload_Builder::build_basic_order( $order );
		wp_send_json_success( array( 'payload' => $payload ) );
	}

	/**
	 * AJAX handler: Submit order to RRD
	 *
	 * Submits the order to the RRD API endpoint.
	 */
	public static function submit_order() {
		$order = self::validate_request( 'rrd_submit_nonce' );

		if ( is_wp_error( $order ) ) {
			wp_send_json_error( array( 'message' => $order->get_error_message() ) );
		}

		if ( ! rrd_validate_configuration() ) {
			wp_send_json_error( array( 'message' => __( 'RRD credentials not configured', 'rrd-woocommerce-integration' ) ) );
		}

		$result = rrd_submit_order_to_api( $order );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => __( 'Order submitted successfully', 'rrd-woocommerce-integration' ) ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ?? __( 'Submission failed', 'rrd-woocommerce-integration' ) ) );
		}
	}
}
