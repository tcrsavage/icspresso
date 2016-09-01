<?php

namespace Icspresso;

class WP_Http extends \WP_Http {

	public function request( $url, $args = array() ) {
		global $wp_version;

		$defaults = array(
			'method' => 'GET',
			/**
			 * Filters the timeout value for an HTTP request.
			 *
			 * @since 2.7.0
			 *
			 * @param int $timeout_value Time in seconds until a request times out.
			 *                           Default 5.
			 */
			'timeout' => apply_filters( 'http_request_timeout', 5 ),
			/**
			 * Filters the number of redirects allowed during an HTTP request.
			 *
			 * @since 2.7.0
			 *
			 * @param int $redirect_count Number of redirects allowed. Default 5.
			 */
			'redirection' => apply_filters( 'http_request_redirection_count', 5 ),
			/**
			 * Filters the version of the HTTP protocol used in a request.
			 *
			 * @since 2.7.0
			 *
			 * @param string $version Version of HTTP used. Accepts '1.0' and '1.1'.
			 *                        Default '1.0'.
			 */
			'httpversion' => apply_filters( 'http_request_version', '1.0' ),
			/**
			 * Filters the user agent value sent with an HTTP request.
			 *
			 * @since 2.7.0
			 *
			 * @param string $user_agent WordPress user agent string.
			 */
			'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) ),
			/**
			 * Filters whether to pass URLs through wp_http_validate_url() in an HTTP request.
			 *
			 * @since 3.6.0
			 *
			 * @param bool $pass_url Whether to pass URLs through wp_http_validate_url().
			 *                       Default false.
			 */
			'reject_unsafe_urls' => apply_filters( 'http_request_reject_unsafe_urls', false ),
			'blocking' => true,
			'headers' => array(),
			'cookies' => array(),
			'body' => null,
			'compress' => false,
			'decompress' => true,
			'sslverify' => true,
			'sslcertificates' => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
			'stream' => false,
			'filename' => null,
			'limit_response_size' => null,
		);

		// Pre-parse for the HEAD checks.
		$args = wp_parse_args( $args );

		// By default, Head requests do not cause redirections.
		if ( isset($args['method']) && 'HEAD' == $args['method'] )
			$defaults['redirection'] = 0;

		$r = wp_parse_args( $args, $defaults );
		/**
		 * Filters the arguments used in an HTTP request.
		 *
		 * @since 2.7.0
		 *
		 * @param array  $r   An array of HTTP request arguments.
		 * @param string $url The request URL.
		 */
		$r = apply_filters( 'http_request_args', $r, $url );

		// The transports decrement this, store a copy of the original value for loop purposes.
		if ( ! isset( $r['_redirection'] ) )
			$r['_redirection'] = $r['redirection'];

		/**
		 * Filters whether to preempt an HTTP request's return value.
		 *
		 * Returning a non-false value from the filter will short-circuit the HTTP request and return
		 * early with that value. A filter should return either:
		 *
		 *  - An array containing 'headers', 'body', 'response', 'cookies', and 'filename' elements
		 *  - A WP_Error instance
		 *  - boolean false (to avoid short-circuiting the response)
		 *
		 * Returning any other value may result in unexpected behaviour.
		 *
		 * @since 2.9.0
		 *
		 * @param false|array|WP_Error $preempt Whether to preempt an HTTP request's return value. Default false.
		 * @param array               $r        HTTP request arguments.
		 * @param string              $url      The request URL.
		 */
		$pre = apply_filters( 'pre_http_request', false, $r, $url );

		if ( false !== $pre )
			return $pre;

		if ( function_exists( 'wp_kses_bad_protocol' ) ) {
			if ( $r['reject_unsafe_urls'] ) {
				$url = wp_http_validate_url( $url );
			}
			if ( $url ) {
				$url = wp_kses_bad_protocol( $url, array( 'http', 'https', 'ssl' ) );
			}
		}

		$arrURL = @parse_url( $url );

		if ( empty( $url ) || empty( $arrURL['scheme'] ) ) {
			return new \WP_Error('http_request_failed', __('A valid URL was not provided.'));
		}

		if ( $this->block_request( $url ) ) {
			return new \WP_Error( 'http_request_failed', __( 'User has blocked requests through HTTP.' ) );
		}

		// If we are streaming to a file but no filename was given drop it in the WP temp dir
		// and pick its name using the basename of the $url
		if ( $r['stream'] ) {
			if ( empty( $r['filename'] ) ) {
				$r['filename'] = get_temp_dir() . basename( $url );
			}

			// Force some settings if we are streaming to a file and check for existence and perms of destination directory
			$r['blocking'] = true;
			if ( ! wp_is_writable( dirname( $r['filename'] ) ) ) {
				return new WP_Error( 'http_request_failed', __( 'Destination directory for file streaming does not exist or is not writable.' ) );
			}
		}

		if ( is_null( $r['headers'] ) ) {
			$r['headers'] = array();
		}

		// WP allows passing in headers as a string, weirdly.
		if ( ! is_array( $r['headers'] ) ) {
			$processedHeaders = \WP_Http::processHeaders( $r['headers'] );
			$r['headers'] = $processedHeaders['headers'];
		}

		// Setup arguments
		$headers = $r['headers'];
		$data = $r['body'];
		$type = $r['method'];
		$options = array(
			'timeout' => $r['timeout'],
			'useragent' => $r['user-agent'],
			'blocking' => $r['blocking'],
			'hooks' => new \Requests_Hooks(),
		);

		// Ensure redirects follow browser behaviour.
		$options['hooks']->register( 'requests.before_redirect', array( get_class(), 'browser_redirect_compatibility' ) );

		if ( $r['stream'] ) {
			$options['filename'] = $r['filename'];
		}
		if ( empty( $r['redirection'] ) ) {
			$options['follow_redirects'] = false;
		} else {
			$options['redirects'] = $r['redirection'];
		}

		// Use byte limit, if we can
		if ( isset( $r['limit_response_size'] ) ) {
			$options['max_bytes'] = $r['limit_response_size'];
		}

		// If we've got cookies, use and convert them to Requests_Cookie.
		if ( ! empty( $r['cookies'] ) ) {
			$options['cookies'] = \WP_Http::normalize_cookies( $r['cookies'] );
		}

		// SSL certificate handling
		if ( ! $r['sslverify'] ) {
			$options['verify'] = false;
		} else {
			$options['verify'] = $r['sslcertificates'];
		}

		// All non-HEAD requests should put the arguments in the form body.
		if ( 'HEAD' !== $type ) {
			$options['data_format'] = 'body';
		}

		/**
		 * Filters whether SSL should be verified for non-local requests.
		 *
		 * @since 2.8.0
		 *
		 * @param bool $ssl_verify Whether to verify the SSL connection. Default true.
		 */
		$options['verify'] = apply_filters( 'https_ssl_verify', $options['verify'] );

		// Check for proxies.
		$proxy = new \WP_HTTP_Proxy();
		if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) ) {
			$options['proxy'] = new \Requests_Proxy_HTTP( $proxy->host() . ':' . $proxy->port() );

			if ( $proxy->use_authentication() ) {
				$options['proxy']->use_authentication = true;
				$options['proxy']->user = $proxy->username();
				$options['proxy']->pass = $proxy->password();
			}
		}

		try {
			$requests_response = \Requests::request( $url, $headers, $data, $type, $options );

			// Convert the response into an array
			$http_response = new \WP_HTTP_Requests_Response( $requests_response, $r['filename'] );
			$response = $http_response->to_array();

			// Add the original object to the array.
			$response['http_response'] = $http_response;
		}
		catch ( \Requests_Exception $e ) {
			$response = new \WP_Error( 'http_request_failed', $e->getMessage() );
		}

		/**
		 * Fires after an HTTP API response is received and before the response is returned.
		 *
		 * @since 2.8.0
		 *
		 * @param array|WP_Error $response HTTP response or WP_Error object.
		 * @param string         $context  Context under which the hook is fired.
		 * @param string         $class    HTTP transport used.
		 * @param array          $args     HTTP request arguments.
		 * @param string         $url      The request URL.
		 */
		do_action( 'http_api_debug', $response, 'response', 'Requests', $r, $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! $r['blocking'] ) {
			return array(
				'headers' => array(),
				'body' => '',
				'response' => array(
					'code' => false,
					'message' => false,
				),
				'cookies' => array(),
				'http_response' => null,
			);
		}

		/**
		 * Filters the HTTP API response immediately before the response is returned.
		 *
		 * @since 2.9.0
		 *
		 * @param array  $response HTTP response.
		 * @param array  $r        HTTP request arguments.
		 * @param string $url      The request URL.
		 */
		return apply_filters( 'http_response', $response, $r, $url );
	}

}