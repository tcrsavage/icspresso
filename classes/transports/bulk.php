<?php

namespace Icspresso\Transports;

class Bulk extends \ElasticSearch\Bulk {

	private $client;

	/**
	 * Construct a bulk operation
	 *
	 * @param \Icspresso\API
	 */

	public function __construct( \Icspresso\API $client ) {
		$this->client = $client;
	}

	/**
	 * commit this operation
	 */
	public function commit() {
		return $this->client->request( array( '_bulk' ), 'POST', $this->createPayload() );
	}

}
