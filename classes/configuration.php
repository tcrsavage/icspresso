<?php

namespace Icspresso;

class Configuration {

	/**
	 * Set the default elasticsearch host address to be used by the elasticsearch API wrapper
	 *
	 * @param $host
	 */
	public static function set_host( $host ) {

		self::set_option( 'server_host', $host );
	}

	/**
	 * Get the  elasticsearch host address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	public static function get_host() {

		$current = defined( 'ICSPRESSO_HOST' ) ? ICSPRESSO_HOST : self::get_option( 'server_host', '' );

		return apply_filters( 'icspresso_host', $current );
	}

	/**
	 * Set the  elasticsearch port address to be used by the elasticsearch API wrapper
	 *
	 * @param $port
	 */
	public static function set_port( $port ) {

		self::set_option( 'server_port', $port );
	}

	/**
	 * Get the  elasticsearch port address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	public static function get_port() {

		$current = defined( 'ICSPRESSO_PORT' ) ? ICSPRESSO_PORT : self::get_option( 'server_port', '' );

		return apply_filters( 'icspresso_port', $current );
	}


	/**
	 * Get the  elasticsearch host protocol to be used by the elasticsearch API wrapper
	 *
	 * @param $protocol
	 */
	public static function set_protocol( $protocol ) {

		self::set_option( 'server_protocol', $protocol );
	}


	/**
	 * Set the  elasticsearch protocol address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	public static function get_protocol() {

		return apply_filters( 'icspresso_protocol', self::get_option( 'server_protocol', 'http' ) );
	}

	/**
	 * Sets the index name to be used
	 *
	 * @param $name
	 */
	public static function set_index_name( $name ) {

		self::set_option( 'index_name', $name );
	}


	/**
	 * Gets the index name to be used
	 *
	 * @return string
	 */
	public static function get_index_name() {

		$current = defined( 'ICSPRESSO_INDEX_NAME' ) ? ICSPRESSO_INDEX_NAME : self::get_option( 'server_port', 'icspresso' );

		return apply_filters( 'icspresso_index_name', $current );
	}

	/**
	 * Set the elasticsearch connection timeout
	 *
	 * @param $timeout
	 */
	public static function set_timeout( $timeout ) {

		self::set_option( 'connection_timeout', $timeout );
	}

	/**
	 * Get the elasticsearch connection timeout
	 *
	 * @return int
	 */
	public static function get_timeout() {

		$current = defined( 'ICSPRESSO_CONNECTION_TIMEOUT' ) ? ICSPRESSO_CONNECTION_TIMEOUT : self::get_option( 'connection_timeout', 10 );

		return apply_filters( 'icspresso_connection_timeout', $current );
	}

	/**
	 * Set the maximum log count
	 *
	 * @param $max_logs
	 */
	public static function set_max_logs( $max_logs ) {

		self::set_option( 'max_logs', $max_logs );
	}

	/**
	 * Get the maximum log count
	 *
	 * @return int
	 */
	public static function get_max_logs() {

		$current = defined( 'ICSPRESSO_MAX_LOGS' ) ? ICSPRESSO_MAX_LOGS : self::get_option( 'max_logs', 50 );

		return apply_filters( 'icspresso_max_logs', $current );
	}


	/**
	 * Set whether logging is enabled
	 *
	 * @param $is_enabled
	 */
	public static function set_is_logging_enabled( $is_enabled ) {

		$is_enabled = ( $is_enabled ) ? '1' : '0';

		self::set_option( 'is_logging_enabled', $is_enabled );
	}

	/**
	 * Get whether logging is enabled
	 *
	 * @return int
	 */
	public static function get_is_logging_enabled() {

		$current = defined( 'ICSPRESSO_IS_LOGGING_ENABLED' ) ? ICSPRESSO_IS_LOGGING_ENABLED : self::get_option( 'is_logging_enabled', '1' );

		return (bool) apply_filters( 'icspresso_is_logging_enabled', $current );
	}

	/**
	 * Set whether or not elasticsearch indexing is enabled
	 *
	 * @param $bool
	 */
	public static function set_is_indexing_enabled( $bool ) {

		$is_enabled = ( $bool ) ? '1' : '0';

		self::set_option( 'indexing_is_enabled', $is_enabled );
	}

	/**
	 * Get whether or not elasticsearch indexing is enabled
	 *
	 * @return mixed|void
	 */
	public static function get_is_indexing_enabled() {

		$current = defined( 'ICSPRESSO_IS_INDEXING_ENABLED' ) ? ICSPRESSO_IS_INDEXING_ENABLED : self::get_option( 'indexing_is_enabled', '0' );

		return (bool) apply_filters( 'icspresso_is_indexing_enabled', $current );
	}

	/**
	 * Set the index creation args for initialising the index
	 *
	 * @param array $args
	 */
	public static function set_index_creation_args( array $args ) {

		self::set_option( 'index_creation_args', $args );
	}

	/**
	 * Get the index creation args for initialising the index
	 *
	 * @return array
	 */
	public static function get_index_creation_args() {

		$args = self::get_option( 'index_creation_args', array() );

		return apply_filters( 'icspresso_index_creation_args', $args );
	}

	/**
	 * Get the active types for indexing (post/comment/term etc);
	 *
	 * @return array    An associative array of slug=>class name
	 */
	public static function get_active_types() {

		$current = self::get_option( 'active_types', false ) ? self::get_option( 'active_types', false ) : array(
			'post'      => __NAMESPACE__ . '\\Types\Post',
			'user'      => __NAMESPACE__ . '\\Types\User',
			'comment'   => __NAMESPACE__ . '\\Types\Comment',
			'term'      => __NAMESPACE__ . '\\Types\Term'
		);

		return apply_filters( 'icspresso_active_types', $current );
	}

	/**
	 * Set whether or not elasticsearch indexing is enabled
	 *
	 * @param $bool
	 */
	public static function set_active_types( $active_types ) {

		self::set_option( 'active_types', $active_types );
	}

	/**
	 * Set elasticsearch option
	 *
	 * @param $name
	 * @param $value
	 */
	public static function set_option( $name, $value ) {

		update_option( 'icspresso_' . $name, $value );
	}

	/**
	 * Get elasticsearch option
	 *
	 * @param $name
	 * @param bool $default
	 * @return mixed|void
	 */
	public static function get_option( $name, $default = false ) {

		return get_option( 'icspresso_' . $name, $default );
	}

}