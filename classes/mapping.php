<?php

namespace Icspresso;

use ElasticSearch\Mapping as Mapping_Base;

class Mapping extends Mapping_Base {

	/**
	 * Export mapping data as a json-ready array
	 *
	 * @return string
	 */
	public function export() {

		$root_object = apply_filters( 'icspresso_root_mapping_' . $this->config['type'], array(
			'numeric_detection'    => true,
			'dynamic_date_formats' => array(
				'yyyy-MM-dd HH:mm:ss',
				'yyyy-MM-dd',
			),
			'dynamic_templates'    => array(
				array(
					'meta_template_1' => array(
						'path_match'         => 'meta.*',
						'match_mapping_type' => 'string',
						'mapping'            => array(
							'index' => 'not_analyzed',
							'type'  => 'string',
						),
					),
				),
			),
		) );

		$root_object['properties'] = $this->properties;

		return $root_object;
	}

}
