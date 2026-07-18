<?php
/**
 * Plugin Name: RRD WooCommerce Integration
 * Plugin URI: https://github.com/darickquinto0629/rrd-woocommerce-integration
 * Description: Integrates WooCommerce orders with RRD createorder API
 * Version: 0.1.0
 * Author: Darick L. Quinto
 * Author URI: https://github.com/darickquinto0629
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rrd-woocommerce-integration
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'RRD_PLUGIN_VERSION', '0.1.0' );
define( 'RRD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'RRD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin files
require_once RRD_PLUGIN_PATH . 'includes/helpers.php';
require_once RRD_PLUGIN_PATH . 'includes/class-rrd-payload-builder.php';
require_once RRD_PLUGIN_PATH . 'includes/class-rrd-api-client.php';
require_once RRD_PLUGIN_PATH . 'includes/class-rrd-response-handler.php';
require_once RRD_PLUGIN_PATH . 'includes/class-rrd-order-service.php';
require_once RRD_PLUGIN_PATH . 'includes/class-rrd-admin.php';
require_once RRD_PLUGIN_PATH . 'includes/class-rrd-ajax.php';
require_once RRD_PLUGIN_PATH . 'includes/order-submission.php';

// Initialize classes
RRD_Admin::init();
RRD_AJAX::init();

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, 'rrd_plugin_activate' );
function rrd_plugin_activate() {
	// Initialize default options if they don't exist
	if ( ! get_option( 'rrd_environment' ) ) {
		update_option( 'rrd_environment', 'qa' );
	}
	if ( ! get_option( 'rrd_client_id' ) ) {
		update_option( 'rrd_client_id', 'ESTRELLITA01' );
	}
}

/**
 * Deactivation Hook
 */
register_deactivation_hook( __FILE__, 'rrd_plugin_deactivate' );
function rrd_plugin_deactivate() {
	// Clean up if needed
	wp_clear_scheduled_hook( 'rrd_check_pending_orders' );
}

/**
 * Add admin menu
 */
add_action( 'admin_menu', 'rrd_add_admin_menu' );
function rrd_add_admin_menu() {
	add_menu_page(
		'RRD Integration',
		'RRD Integration',
		'manage_options',
		'rrd-integration',
		'rrd_admin_page',
		'dashicons-share-alt2',
		56
	);

	add_submenu_page(
		'rrd-integration',
		'Settings',
		'Settings',
		'manage_options',
		'rrd-settings',
		'rrd_settings_page'
	);
}

/**
 * Main admin page
 */
function rrd_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'rrd-woocommerce-integration' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'RRD WooCommerce Integration', 'rrd-woocommerce-integration' ); ?></h1>
		<p><?php esc_html_e( 'Welcome to the RRD WooCommerce Integration dashboard.', 'rrd-woocommerce-integration' ); ?></p>
		<p><?php esc_html_e( 'Configure your RRD API credentials in the Settings tab to get started.', 'rrd-woocommerce-integration' ); ?></p>
	</div>
	<?php
}

/**
 * Settings page
 */
function rrd_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'rrd-woocommerce-integration' ) );
	}

	// Handle form submission
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['rrd_settings_nonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rrd_settings_nonce'] ) ), 'rrd_settings_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'rrd-woocommerce-integration' ) );
		}

		// Update options
		update_option( 'rrd_environment', sanitize_text_field( wp_unslash( $_POST['rrd_environment'] ?? 'qa' ) ) );
		update_option( 'rrd_client_id', sanitize_text_field( wp_unslash( $_POST['rrd_client_id'] ?? '' ) ) );
		update_option( 'rrd_api_username', sanitize_text_field( wp_unslash( $_POST['rrd_api_username'] ?? '' ) ) );
		update_option( 'rrd_api_password', sanitize_text_field( wp_unslash( $_POST['rrd_api_password'] ?? '' ) ) );

		echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'rrd-woocommerce-integration' ) . '</p></div>';
	}

	// Get current values
	$environment    = get_option( 'rrd_environment', 'qa' );
	$client_id      = get_option( 'rrd_client_id', '' );
	$api_username   = get_option( 'rrd_api_username', '' );
	$api_password   = get_option( 'rrd_api_password', '' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'RRD API Settings', 'rrd-woocommerce-integration' ); ?></h1>
		<p><?php esc_html_e( 'Configure your RRD API credentials below. Your credentials are stored securely in WordPress options and will be masked in all logs.', 'rrd-woocommerce-integration' ); ?></p>

		<form method="POST" action="">
			<?php wp_nonce_field( 'rrd_settings_action', 'rrd_settings_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th scope="row"><label for="rrd_environment"><?php esc_html_e( 'Environment', 'rrd-woocommerce-integration' ); ?></label></th>
					<td>
						<select name="rrd_environment" id="rrd_environment" required>
							<option value="qa" <?php selected( $environment, 'qa' ); ?>>QA / Staging</option>
							<option value="prod" <?php selected( $environment, 'prod' ); ?>>Production</option>
						</select>
						<p class="description"><?php esc_html_e( 'Select QA for testing, Production for live orders.', 'rrd-woocommerce-integration' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="rrd_client_id"><?php esc_html_e( 'Client ID', 'rrd-woocommerce-integration' ); ?></label></th>
					<td>
						<input type="text" name="rrd_client_id" id="rrd_client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text" required>
						<p class="description"><?php esc_html_e( 'Your RRD-provided Client ID (e.g., ESTRELLITA01)', 'rrd-woocommerce-integration' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="rrd_api_username"><?php esc_html_e( 'API Username', 'rrd-woocommerce-integration' ); ?></label></th>
					<td>
						<input type="text" name="rrd_api_username" id="rrd_api_username" value="<?php echo esc_attr( $api_username ); ?>" class="regular-text" required>
						<p class="description"><?php esc_html_e( 'RRD-provided Basic Auth username', 'rrd-woocommerce-integration' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="rrd_api_password"><?php esc_html_e( 'API Password', 'rrd-woocommerce-integration' ); ?></label></th>
					<td>
						<input type="password" name="rrd_api_password" id="rrd_api_password" value="<?php echo esc_attr( $api_password ); ?>" class="regular-text" required>
						<p class="description"><?php esc_html_e( 'RRD-provided Basic Auth password (masked in logs)', 'rrd-woocommerce-integration' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( esc_html__( 'Save Settings', 'rrd-woocommerce-integration' ) ); ?>
		</form>

		<hr>

		<h2><?php esc_html_e( 'API Endpoints', 'rrd-woocommerce-integration' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Environment', 'rrd-woocommerce-integration' ); ?></th>
					<th><?php esc_html_e( 'Endpoint URL', 'rrd-woocommerce-integration' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'QA / Staging', 'rrd-woocommerce-integration' ); ?></strong></td>
					<td><code>https://api85-qa.rrd.com/corporate/v1/createorder</code></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Production', 'rrd-woocommerce-integration' ); ?></strong></td>
					<td><code>https://api85.rrd.com/corporate/v1/createorder</code></td>
				</tr>
			</tbody>
		</table>
	</div>
	<?php
}
