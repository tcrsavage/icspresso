<?php

namespace Icspresso\Transports;

use ElasticSearch;
use Icspresso\Logger;

class WP_HTTP extends \ElasticSearch\Transport\HTTP {

	static $protocol = "http";

	var $is_logging_enabled = null;

	function disable_logging() {

		$this->is_logging_enabled = false;
	}

	function enable_logging() {

		$this->is_logging_enabled = true;
	}

	function is_logging_enabled() {

		if ( $this->is_logging_enabled !== null ) {
			return $this->is_logging_enabled();
		}

		return \Icspresso\get_default_configuration()->get_is_logging_enabled();
	}

	/**
	 * Perform a http call against an url with an optional payload
	 *
	 * @return array
	 * @param string     $url
	 * @param string     $method  (GET/POST/PUT/DELETE)
	 * @param array|bool $payload The document/instructions to pass along
	 * @throws \HTTPException
	 */
	protected function call( $url, $method = 'GET', $payload = null ) {
		global $wp_version;

		$http        = version_compare( $wp_version, '4.6', '<' ) ? new \WP_Http : new \Icspresso\WP_Http;
		$request_url = static::$protocol . "://" . $this->host . ':' . $this->port . $url;

		//For compatibility with original transports handling
		if ( is_array( $payload ) && count( $payload ) > 0 ) {
			$body = json_encode( $payload );
		} else {
			$body = $payload;
		}

		$r = $http->request( $request_url, array(
			'timeout'   => $this->getTimeout(),
			'method'    => strtoupper( $method ),
			'body'      => $body,
			'sslverify' => false,
			'headers'   => array( 'Host' => $this->host . ':' . $this->port ),
		) );

		if ( is_wp_error( $r ) ) {

			$data = array( 'error' => $r->get_error_message(), "code" => $r->get_error_code() );

			if ( $this->is_logging_enabled() ) {
				Logger::log_failed_request( $request_url, $method, $payload, $data );
			}

			return $data;
		}

		$data = json_decode( $r['body'], true );

		if ( (int) $r['response']['code'] > 299 && $this->is_logging_enabled ) {

			Logger::log_failed_request( $request_url, $method, $payload, $data );
		}

		return $data;
	}

}
