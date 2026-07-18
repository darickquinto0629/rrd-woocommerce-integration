<?php
/**
 * RRD API Client
 *
 * Responsible for communicating with the RRD API endpoint.
 * Handles HTTP requests, response parsing, and error handling.
 *
 * @package RRD_WooCommerce_Integration
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RRD_API_Client
 *
 * Handles HTTP communication with the RRD API endpoint.
 */
class RRD_API_Client {

	/**
	 * Submit payload to RRD API
	 *
	 * Makes an HTTP POST request to the RRD endpoint with the provided payload.
	 * Parses the response and returns structured data.
	 *
	 * @param string $payload_json The JSON payload to submit.
	 * @return array Array containing:
	 *   - 'return_code' (int): API return code or HTTP status code
	 *   - 'description' (string): API description or empty string
	 *   - 'response_body' (string): Raw response body
	 *   - 'http_code' (int): HTTP status code
	 * @throws Exception On network error or failed request.
	 */
	public static function submit( $payload_json ) {
		// Get endpoint and headers
		$endpoint = rrd_get_api_endpoint();
		$headers = rrd_get_api_headers();

		// Make API request
		$response = wp_remote_post( $endpoint, array(
			'method'    => 'POST',
			'headers'   => $headers,
			'body'      => $payload_json,
			'timeout'   => 30,
			'sslverify' => true,
		) );

		// Check for network/connection errors
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			throw new Exception( 'Network error: ' . $error_message );
		}

		// Get HTTP response code and body
		$http_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Parse RRD response JSON
		$response_data = json_decode( $response_body, true );
		$return_code = $response_data['ReturnCode'] ?? $http_code;
		$description = $response_data['Description'] ?? '';

		return array(
			'return_code'   => $return_code,
			'description'   => $description,
			'response_body' => $response_body,
			'http_code'     => $http_code,
		);
	}
}
