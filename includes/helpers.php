<?php
/**
 * Helper functions for RRD API communication
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get RRD API endpoint URL based on environment
 *
 * @return string
 */
function rrd_get_api_endpoint() {
	$environment = get_option( 'rrd_environment', 'qa' );
	
	if ( 'prod' === $environment ) {
		return 'https://api85.rrd.com/corporate/v1/createorder';
	}
	
	return 'https://api85-qa.rrd.com/corporate/v1/createorder';
}

/**
 * Get Basic Auth header
 *
 * @return string Base64 encoded credentials
 */
function rrd_get_basic_auth_header() {
	$username = get_option( 'rrd_api_username', '' );
	$password = get_option( 'rrd_api_password', '' );
	
	if ( ! $username || ! $password ) {
		return '';
	}
	
	return 'Basic ' . base64_encode( $username . ':' . $password );
}

/**
 * Get API headers
 *
 * @return array
 */
function rrd_get_api_headers() {
	$auth = rrd_get_basic_auth_header();
	
	$headers = array(
		'Content-Type' => 'application/json',
		'Accept'       => 'application/json',
	);
	
	if ( $auth ) {
		$headers['Authorization'] = $auth;
	}
	
	return $headers;
}

/**
 * Log API request/response with masked credentials
 *
 * Debug logging only - logs to error log for troubleshooting.
 * Order notes are handled separately by RRD_Response_Handler for status changes.
 *
 * @param string $action  Log action (sending_request, submission_success, submission_error, etc.)
 * @param mixed  $data    Data to log
 * @param int    $order_id Order ID (optional, for context only)
 */
function rrd_log( $action, $data, $order_id = 0 ) {
	$log_entry = array(
		'timestamp' => current_time( 'mysql' ),
		'action'    => $action,
		'order_id'  => $order_id,
		'data'      => $data,
	);
	
	// Log to error_log only (for developers/debugging)
	// Order status notes are added by RRD_Response_Handler, not here
	error_log( 'RRD: ' . wp_json_encode( $log_entry ) );
}

/**
 * Mask sensitive data in logs
 *
 * @param array $data Data to mask
 * @return array Masked data
 */
function rrd_mask_sensitive_data( $data ) {
	$sensitive_keys = array( 'Authorization', 'password', 'rrd_api_password' );
	
	$masked = $data;
	foreach ( $sensitive_keys as $key ) {
		if ( isset( $masked[ $key ] ) ) {
			$masked[ $key ] = '***MASKED***';
		}
	}
	
	return $masked;
}

/**
 * Validate plugin configuration
 *
 * @return array with 'valid' => bool and 'message' => string
 */
function rrd_validate_configuration() {
	$client_id      = get_option( 'rrd_client_id', '' );
	$api_username   = get_option( 'rrd_api_username', '' );
	$api_password   = get_option( 'rrd_api_password', '' );
	
	if ( ! $client_id ) {
		return array(
			'valid'   => false,
			'message' => 'Client ID is not configured.',
		);
	}
	
	if ( ! $api_username ) {
		return array(
			'valid'   => false,
			'message' => 'API Username is not configured.',
		);
	}
	
	if ( ! $api_password ) {
		return array(
			'valid'   => false,
			'message' => 'API Password is not configured.',
		);
	}
	
	return array(
		'valid'   => true,
		'message' => 'Configuration is valid.',
	);
}
