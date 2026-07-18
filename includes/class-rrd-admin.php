<?php
/**
 * RRD Admin Handler
 *
 * Responsible for admin UI, meta box rendering, and asset management.
 * Displays order status, submission buttons, and payload/response viewers.
 *
 * @package RRD_WooCommerce_Integration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RRD_Admin
 *
 * Handles admin interface and meta box management.
 */
class RRD_Admin {

	/**
	 * Initialize admin handler
	 *
	 * Registers all WordPress hooks for admin pages and meta boxes.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'render_meta_box' ) );
	}

	/**
	 * Enqueue CSS and JS for order submission
	 */
	public static function enqueue_assets() {
		// Check if this is a WooCommerce order page (works for both legacy and HPOS)
		$screen = get_current_screen();
		
		// Only load on WooCommerce order pages
		if ( ! $screen || ( $screen->post_type !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders' ) ) {
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
			array( 'jquery' ),
			RRD_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Render RRD meta box on order page
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 */
	public static function render_meta_box( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		self::render_status_section( $order );
	}

	/**
	 * Render RRD order status section (HPOS)
	 *
	 * @param WC_Order $order WooCommerce order object
	 */
	private static function render_status_section( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$status = self::get_status_display_data( $order->get_id() );
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
			self::localize_script( $order->get_id() );
			do_action( 'rrd_order_submission_localized' );
		}
	}

	/**
	 * Localize script with nonces and AJAX data
	 *
	 * @param int $order_id The order ID.
	 */
	private static function localize_script( $order_id ) {
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
	 * Get order submission status display data
	 *
	 * @param int $order_id WooCommerce order ID
	 * @return array Status information for display
	 */
	public static function get_status_display_data( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return self::get_default_status();
		}

		$status = $order->get_meta( 'rrd_submission_status' ) ?: 'never_sent';

		return array(
			'status'                  => $status,
			'status_label'            => self::get_status_label( $status ),
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
	 * @return string Localized status label
	 */
	private static function get_status_label( $status ) {
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
	 * Get default order status array
	 *
	 * @return array Default status data
	 */
	private static function get_default_status() {
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
}
