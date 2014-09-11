<?php

namespace HMES;

class Logger {

	static $max_logs = 500;

	/**
	 * Log a failed request to elasticsearch
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return array
	 */
	static function log_failed_request( $url, $payload, $response ) {

		//Elastic search response error messages are long - explode off semicolon to get the short error title
		if ( ! empty( $response['error'] ) ) {
			$exploded = explode( ';', $response['error'] );
			$message  = reset( $exploded );
		} else {
			$message = '-';
		}

		self::save_log( array(
			'type'      => 'error',
			'message'   => $message,
			'data'      => array( 'url' => $url, 'payload' => $payload, 'response' => $response ),
		) );
	}

	/**
	 * Get a paginated array of log entries (descending on date created)
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return array
	 */
	static function get_paginated_logs( $page = 1, $per_page = 50 ) {

		if ( ! $page || $page < 0 ) {
			$page = 1;
		}

		if ( ! $per_page || $per_page < 0 ) {
			$per_page = 50;
		}

		$saved = self::get_logs();
		$saved = array_reverse( $saved, true );
		$logs  = array_slice( $saved, ( ( $page - 1 ) * $per_page ), ( $per_page * $page ), true );

		return $logs;
	}

	/**
	 * Get all log entries
	 *
	 * @return mixed|void
	 */
	static function get_logs() {

		return get_option( 'hmes_logger_logs', array() );
	}

	/**
	 * Set log entries
	 *
	 * @param $logs
	 */
	static function set_logs( $logs ) {

		delete_option( 'hmes_logger_logs' );
		add_option( 'hmes_logger_logs', $logs, '', 'no' );
	}

	/**
	 * Count the number of log entries
	 *
	 * @return int
	 */
	static function count_logs() {

		return count( self::get_logs( 'logs', array() ) );
	}

	/**
	 * Save a log entry
	 *
	 * @param $item
	 */
	static function save_log( $item ) {

		$item = wp_parse_args( $item, array(
			'type'      => 'notice',
			'timestamp' => time(),
			'message'   => '-',
			'data'      => '-',
		) );

		$saved = self::get_logs();

		if ( empty( $saved ) ) {

			$saved = array( 1 => $item );
		} else {

			$saved[] = $item;
		}

		if ( count( $saved ) > self::$max_logs ) {
			$saved = array_slice( $saved, -self::$max_logs, self::$max_logs, true );
		}

		self::set_logs( $saved );
	}
}