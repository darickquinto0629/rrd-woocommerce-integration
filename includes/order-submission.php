<?php
/**
 * Order Submission - Manual RRD API submission from WooCommerce order page
 *
 * Handles:
 * - Order meta box UI with status display
 * - Payload preview generation
 * - Manual order submission to RRD API
 * - Response handling and storage
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue CSS and JS for order submission
 */
add_action( 'admin_enqueue_scripts', 'rrd_enqueue_order_submission_assets' );
function rrd_enqueue_order_submission_assets() {
	// Only load on order pages
	if ( empty( $_GET['id'] ) || strpos( $_SERVER['REQUEST_URI'], 'page=wc-orders' ) === false ) {
		return;
	}

	wp_enqueue_style(
		'rrd-order-submission',
		RRD_PLUGIN_URL . 'assets/css/order-submission.css',
		array(),
		RRD_PLUGIN_VERSION
	);

	wp_enqueue_script(
		'rrd-order-submission',
		RRD_PLUGIN_URL . 'assets/js/order-submission.js',
		array(),
		RRD_PLUGIN_VERSION,
		true
	);
}

/**
 * Display RRD section on HPOS order admin
 * Using the proper WooCommerce hook for displaying content after shipping address
 */
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'rrd_render_order_section_admin' );
function rrd_render_order_section_admin( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	rrd_render_order_status_section( $order );
}

/**
 * Render RRD order status section (HPOS)
 *
 * @param WC_Order $order WooCommerce order object
 */
function rrd_render_order_status_section( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$status = rrd_get_order_submission_status( $order->get_id() );
	$config_valid = rrd_validate_configuration();

	echo '<div class="woocommerce-order-data-panel">';
	echo '<h3 style="margin: 1.5em 0 0.5em 0; padding: 1em 0 0.5em 0; border-top: 1px solid #e5e5e5;">' . esc_html__( 'RRD Integration Status', 'rrd-woocommerce-integration' ) . '</h3>';
	?>
	<div class="rrd-meta-box" data-rrd-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
		<!-- Status Display -->
		<div class="rrd-status-item">
			<div class="rrd-status-label">
				<?php esc_html_e( 'Status:', 'rrd-woocommerce-integration' ); ?>
				<span class="rrd-status-badge <?php echo esc_attr( $status['status'] ); ?>">
					<?php echo esc_html( $status['status_label'] ); ?>
				</span>
			</div>
		</div>

		<?php if ( $status['last_submitted_at'] ) : ?>
			<div class="rrd-status-item">
				<div class="rrd-status-label"><?php esc_html_e( 'Last Submitted:', 'rrd-woocommerce-integration' ); ?></div>
				<div class="rrd-status-value"><?php echo esc_html( $status['last_submitted_at'] ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( $status['return_code'] ) : ?>
			<div class="rrd-status-item">
				<div class="rrd-status-label"><?php esc_html_e( 'Return Code:', 'rrd-woocommerce-integration' ); ?></div>
				<div class="rrd-status-value"><?php echo esc_html( $status['return_code'] ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( $status['description'] ) : ?>
			<div class="rrd-status-item">
				<div class="rrd-status-label"><?php esc_html_e( 'Description:', 'rrd-woocommerce-integration' ); ?></div>
				<div class="rrd-status-value"><?php echo esc_html( $status['description'] ); ?></div>
			</div>
		<?php endif; ?>

		<!-- Action Buttons -->
		<div class="rrd-buttons">
			<button type="button" class="rrd-button" id="rrd-preview-button">
				<?php esc_html_e( 'Generate Payload Preview', 'rrd-woocommerce-integration' ); ?>
			</button>

			<button
				type="button"
				class="rrd-button"
				id="rrd-submit-button"
				<?php disabled( ! $config_valid || $status['status'] !== 'never_sent' ); ?>
			>
				<?php esc_html_e( 'Send to RRD', 'rrd-woocommerce-integration' ); ?>
			</button>
		</div>

		<?php if ( ! $config_valid ) : ?>
			<div class="rrd-info-message">
				<?php esc_html_e( '⚠️ Please configure your RRD API credentials in Settings before sending orders.', 'rrd-woocommerce-integration' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $status['status'] !== 'never_sent' ) : ?>
			<div class="rrd-info-message">
				<?php esc_html_e( 'ℹ️ This order has already been submitted. Manual resend is coming in a future update.', 'rrd-woocommerce-integration' ); ?>
			</div>
		<?php endif; ?>

		<!-- Payload Preview (Collapsible) -->
		<div class="rrd-collapsible">
			<div class="rrd-collapsible-header">
				<?php esc_html_e( '▼ Last Request Payload', 'rrd-woocommerce-integration' ); ?>
			</div>
			<div class="rrd-collapsible-content">
				<?php if ( $status['last_request_payload'] ) : ?>
					<div class="rrd-json"><?php echo esc_html( $status['last_request_payload'] ); ?></div>
				<?php else : ?>
					<p><?php esc_html_e( 'No payload sent yet.', 'rrd-woocommerce-integration' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Response (Collapsible) -->
		<div class="rrd-collapsible">
			<div class="rrd-collapsible-header">
				<?php esc_html_e( '▼ Last Response', 'rrd-woocommerce-integration' ); ?>
			</div>
			<div class="rrd-collapsible-content">
				<?php if ( $status['last_response_body'] ) : ?>
					<div class="rrd-json"><?php echo esc_html( $status['last_response_body'] ); ?></div>
				<?php else : ?>
					<p><?php esc_html_e( 'No response received yet.', 'rrd-woocommerce-integration' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Loading Spinner -->
		<div class="rrd-loading" id="rrd-loading">
			<div class="rrd-spinner"></div>
		</div>
	</div>
	</div>
	<?php

	// Localize script with nonces and data - only on first call
	if ( ! did_action( 'rrd_order_submission_localized' ) ) {
		rrd_localize_order_submission_script();
		do_action( 'rrd_order_submission_localized' );
	}
}

/**
 * Localize script with nonces and AJAX data
 */
function rrd_localize_order_submission_script() {
	// Get order ID from current request
	$order_id = intval( $_GET['id'] ?? 0 );

	wp_localize_script(
		'rrd-order-submission',
		'rrdOrderSubmission',
		array(
			'orderId'       => $order_id,
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'previewNonce'  => wp_create_nonce( 'rrd_preview_nonce' ),
			'submitNonce'   => wp_create_nonce( 'rrd_submit_nonce' ),
			'confirmText'   => __( 'Are you sure you want to send this order to RRD?', 'rrd-woocommerce-integration' ),
		)
	);
}

/**
 * Get order submission status from order meta
 *
 * @param int $order_id WooCommerce order ID
 * @return array Status information
 */
function rrd_get_order_submission_status( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return rrd_default_order_status();
	}

	$status = $order->get_meta( 'rrd_submission_status' ) ?: 'never_sent';

	return array(
		'status'                  => $status,
		'status_label'            => rrd_get_status_label( $status ),
		'last_submitted_at'       => $order->get_meta( 'rrd_last_submitted_at' ) ?: '',
		'return_code'             => $order->get_meta( 'rrd_return_code' ) ?: '',
		'description'             => $order->get_meta( 'rrd_description' ) ?: '',
		'last_request_payload'    => $order->get_meta( 'rrd_last_request_payload' ) ?: '',
		'last_response_body'      => $order->get_meta( 'rrd_last_response_body' ) ?: '',
		'submit_count'            => (int) $order->get_meta( 'rrd_submit_count' ) ?: 0,
	);
}

/**
 * Get status label for display
 *
 * @param string $status
 * @return string
 */
function rrd_get_status_label( $status ) {
	$labels = array(
		'never_sent' => __( 'Never Sent', 'rrd-woocommerce-integration' ),
		'pending'    => __( 'Pending', 'rrd-woocommerce-integration' ),
		'success'    => __( 'Success', 'rrd-woocommerce-integration' ),
		'failed'     => __( 'Failed', 'rrd-woocommerce-integration' ),
		'retrying'   => __( 'Retrying', 'rrd-woocommerce-integration' ),
	);

	return $labels[ $status ] ?? $status;
}

/**
 * Get default order status
 *
 * @return array
 */
function rrd_default_order_status() {
	return array(
		'status'               => 'never_sent',
		'status_label'         => __( 'Never Sent', 'rrd-woocommerce-integration' ),
		'last_submitted_at'    => '',
		'return_code'          => '',
		'description'          => '',
		'last_request_payload' => '',
		'last_response_body'   => '',
		'submit_count'         => 0,
	);
}

/**
 * Validate AJAX request (nonce, permissions, order)
 *
 * @param string $nonce_key POST nonce key
 * @return WC_Order|WP_Error Order object or error
 */
function rrd_validate_ajax_request( $nonce_key ) {
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
 */
add_action( 'wp_ajax_rrd_preview_payload', 'rrd_ajax_preview_payload' );
function rrd_ajax_preview_payload() {
	$order = rrd_validate_ajax_request( 'rrd_preview_nonce' );

	if ( is_wp_error( $order ) ) {
		wp_send_json_error( array( 'message' => $order->get_error_message() ) );
	}

	$payload = rrd_generate_payload_preview( $order );
	wp_send_json_success( array( 'payload' => $payload ) );
}

/**
 * AJAX handler: Submit order to RRD
 */
add_action( 'wp_ajax_rrd_submit_order', 'rrd_ajax_submit_order' );
function rrd_ajax_submit_order() {
	$order = rrd_validate_ajax_request( 'rrd_submit_nonce' );

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

/**
 * Generate payload preview (placeholder for Step 5)
 *
 * @param WC_Order $order
 * @return array
 */
function rrd_generate_payload_preview( $order ) {
	// This is a placeholder. Step 5 will implement actual payload building.
	// For now, return a basic structure that shows order data.

	$payload = array(
		'OrderType'       => 'BasicOrder',
		'ClientId'        => get_option( 'rrd_client_id', '' ),
		'PONumber'        => 'WC-' . $order->get_order_number(),
		'SalesOrderNumber' => 'WC-' . $order->get_order_number(),
		'Line'            => array(
			array(
				'LineNumber'  => 1,
				'CustomerSKU' => 'PLACEHOLDER-SKU',
				'Quantity'    => 1,
				'UOM'         => 'EA',
			),
		),
		'ShipToCode'      => 0,
		'ShipToName'      => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
		'ShipToAddress1'  => $order->get_shipping_address_1(),
		'ShipToCity'      => $order->get_shipping_city(),
		'ShipToState'     => $order->get_shipping_state(),
		'ShipToZip'       => $order->get_shipping_postcode(),
		'ShipToCountry'   => $order->get_shipping_country(),
		'ShipToPhone'     => $order->get_billing_phone(),
		'_note'           => '[PLACEHOLDER] This is a preview. Step 5 will implement full payload mapping.',
	);

	return $payload;
}

/**
 * Submit order to RRD API
 *
 * @param WC_Order $order
 * @return array Success/failure result
 */
function rrd_submit_order_to_api( $order ) {
	$order_id = $order->get_id();

	try {
		// Update status to pending
		$order->update_meta_data( 'rrd_submission_status', 'pending' );
		$order->update_meta_data( 'rrd_submit_count', (int) $order->get_meta( 'rrd_submit_count' ) + 1 );
		$order->save();

		// Generate payload
		$payload = rrd_generate_payload_preview( $order );
		$payload_json = wp_json_encode( $payload );

		// Store request payload
		$order->update_meta_data( 'rrd_last_request_payload', $payload_json );
		$order->save();

		// Log request (with masked credentials)
		rrd_log( 'sending_request', array(
			'order_id'  => $order_id,
			'endpoint'  => rrd_get_api_endpoint(),
			'payload'   => $payload,
		), $order_id );

		// TODO: Step 6 - Send actual API request using wp_remote_post()
		// For now, we'll simulate a successful response

		// Simulate API response for testing
		$response_body = wp_json_encode( array(
			'ReturnCode'   => 200,
			'Description'  => 'Order received (simulated response)',
			'PONumber'     => $payload['PONumber'],
		) );

		$return_code = 200;

		// Store response
		$order->update_meta_data( 'rrd_last_response_body', $response_body );
		$order->update_meta_data( 'rrd_last_submitted_at', current_time( 'mysql' ) );
		$order->update_meta_data( 'rrd_return_code', $return_code );
		$order->update_meta_data( 'rrd_description', 'Successfully submitted' );

		if ( 200 === $return_code ) {
			$order->update_meta_data( 'rrd_submission_status', 'success' );
			$order->add_order_note( sprintf(
				/* translators: %s: Return code */
				__( '[RRD] Order successfully submitted. Return Code: %s', 'rrd-woocommerce-integration' ),
				$return_code
			) );

			rrd_log( 'submission_success', array(
				'order_id'    => $order_id,
				'return_code' => $return_code,
			), $order_id );
		} else {
			$order->update_meta_data( 'rrd_submission_status', 'failed' );
			$order->add_order_note( sprintf(
				/* translators: %1$s: Return code, %2$s: Description */
				__( '[RRD] Submission failed. Code: %1$s, Description: %2$s', 'rrd-woocommerce-integration' ),
				$return_code,
				'API Error'
			) );

			rrd_log( 'submission_failed', array(
				'order_id'    => $order_id,
				'return_code' => $return_code,
				'response'    => $response_body,
			), $order_id );
		}

		$order->save();

		return array(
			'success' => 200 === $return_code,
			'message' => 'Submission processed',
			'code'    => $return_code,
		);
	} catch ( Exception $e ) {
		$order->update_meta_data( 'rrd_submission_status', 'failed' );
		$order->add_order_note( sprintf(
			/* translators: %s: Error message */
			__( '[RRD] Submission error: %s', 'rrd-woocommerce-integration' ),
			$e->getMessage()
		) );
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
