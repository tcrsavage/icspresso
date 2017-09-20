<?php

namespace Icspresso;

use Icspresso\Transports\WP_HTTP;
use Icspresso\Transports\WP_HTTPS;
use ElasticSearch\Client;

class API extends Client {

	/**
	 * Hacked in due to private {transport} ref on \ElasticSearch\Client
	 *
	 * @var
	 */
	protected $transport_ref;

	/**
	 * @var Configuration
	 */
	private $configuration;

	/**
	 * @param Configuration $configuration
	 */
	public function __construct( Configuration $configuration ) {

		$this->configuration = $configuration;

		parent::__construct( $this->get_transport(), $this->configuration->get_index_name() );
	}

	/**
	 * Disable logging for calls made by this API instance
	 */
	public function disable_logging() {

		$this->get_connection()->disable_logging();
	}

	/**
	 * Enable logging for calls made by this API instance
	 */
	public function enable_logging() {

		$this->get_connection()->enable_logging();
	}

	/**
	 * Get an elasticsearch Transport HTTP wrapper
	 *
	 * @return bool|Transports\WP_HTTP
	 */
	public function get_connection() {

		return $this->get_transport();
	}

	/**
	 * Get status of the elasticsearch index
	 *
	 * @return array
	 */
	public function get_status() {

		$r = $this->get_connection()->request( array( '_cluster/health' ) );

		return $r;
	}

	/**
	 * Check if a connection to the elasticsearch server is available
	 *
	 * @return bool
	 */
	public function is_connection_available() {

		if ( ! $this->configuration->get_host() || ! $this->configuration->get_port() ) {
			return false;
		}

		$c = $this->get_connection();
		$c->setIndex( '' );
		$r = $c->request( '/_cluster/health', 'GET', array() );
		$c->setIndex( $this->configuration->get_index_name() );

		return ( empty( $r['error'] ) ) ? true : false;
	}

	/**
	 * Check if the default elasticsearch index is created
	 *
	 * @return bool
	 */
	public function is_index_created() {

		if ( ! $this->configuration->get_host() || ! $this->configuration->get_port() || ! $this->configuration->get_index_name() ) {
			return false;
		}

		$r = $this->get_status();

		return ( empty( $r['error'] ) || strpos( $r['error'], 'IndexMissingException' ) === false ) ? true : false;
	}

	/**
	 * Create the elasticsearch index
	 *
	 * @param array $args
	 * @return array
	 */
	public function create_index( $args = array() ) {

		$args = wp_parse_args( $args, $this->configuration->get_index_creation_args() );

		$r = $this->get_connection()->request( false, 'PUT', $args );

		return $r;
	}

	/**
	 * Delete the elasticsearch index
	 *
	 * @param array $args
	 * @return array|bool
	 */
	public function delete_index( $args = array() ) {

		$r = $this->get_connection()->request( false, 'DELETE', $args );

		return $r;
	}

	/**
	 * Get the current transport object
	 *
	 * @return WP_HTTP
	 */
	public function get_transport() {

		if ( ! $this->transport_ref ) {

			$protocol = $this->configuration->get_protocol();
			$host = $this->configuration->get_host();
			$port = $this->configuration->get_port();
			$timeout = $this->configuration->get_timeout();

			if ( strtolower( $protocol ) === 'https' ) {

				$this->transport_ref = new WP_HTTPS( $host, $port, $timeout );

			} else {

				$this->transport_ref = new WP_HTTP( $host, $port, $timeout );

			}
		}

		return $this->transport_ref;
	}

	/**
	 * Create a bulk-transaction
	 *
	 * @return \Elasticsearch\Bulk
	 */
	public function createBulk() {
		return new Transports\Bulk( $this );
	}

	/**
	 * Puts a mapping on index, overwriting so we can add root level config
	 *
	 * @param array|object $mapping
	 * @param array        $config
	 * @return array
	 */
	public function map( $mapping, array $config = array() ) {
		if ( is_array( $mapping ) ) {
			$mapping = new Mapping( $mapping );
		}

		return parent::map( $mapping, $config );
	}

}
