<?php

namespace Icspresso;

class Mapping extends \ElasticSearch\Mapping {

	/**
	 * Export mapping data as a json-ready array
	 *
	 * @return string
	 */
	public function export() {

		$root_object = apply_filters( 'icspresso_root_mapping_' . $this->config['type'], array() );
		$root_object['properties'] = $this->properties;

		return $root_object;
	}

}
