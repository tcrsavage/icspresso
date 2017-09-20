<?php

/*
Plugin Name: Icspresso
Description: Developer's ElasticSearch integration for WordPress
Author: Theo Savage
Version: 0.1
Author URI: http://hmn.md/
*/

namespace Icspresso;


include_dir( __DIR__ . '/lib/elasticsearch/src' );
include_dir( __DIR__ . '/classes/transports' );
include_dir( __DIR__ . '/classes/types' );

require_once( __DIR__ . '/icspresso-admin.php' );
require_once( __DIR__ . '/classes/api.php' );
require_once( __DIR__ . '/classes/mapping.php' );
require_once( __DIR__ . '/classes/configuration.php' );
require_once( __DIR__ . '/classes/logger.php' );
require_once( __DIR__ . '/classes/master.php' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include_dir( __DIR__ . '/classes/cli' );
}

/**
 * Init ell Icspresso type classes on plugins_loaded hook
 */
function init() {

	$master = Master::get_instance();
	$master->initialise();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

/**
 * Get the default configuration class used by default for all API interaction
 *
 * @return Configuration
 */
function get_default_configuration() {

	return apply_filters( 'icspresso_default_configuration', new Configuration() );
}

/**
 * Load a template file from the templates directory
 *
 * Template markup is output, not returned
 *
 * @param $template
 */
function load_template( $template ) {

	if ( file_exists( __DIR__  . '/templates/' . $template . '.php' ) ) {
		include __DIR__  . '/templates/' . $template . '.php';
	}
}

/**
 * Init elasticsearch for given connection and index/type args
 *
 * @param array $connection_args
 * @param array $index_creation_args
 * @return array|bool
 */
function init_elastic_search_index( $index_creation_args = array() ) {

	$es = new API( get_default_configuration() );

	$es->disable_logging();

	if ( ! $es->is_connection_available() ) {
		return false;
	}

	if ( ! $es->is_index_created() ) {

		return $es->create_index( $index_creation_args );
	}

	return false;
}

/**
 * Delete elasticsearch for given connection and index/type args
 *
 * @param array $connection_args
 * @param array $index_deletion_args
 * @return array|bool|\Exception
 */
function delete_elastic_search_index( $connection_args = array(), $index_deletion_args = array() ) {

	$es = new API( $connection_args );

	$es->disable_logging();

	if ( ! $es->is_connection_available() ) {
		return false;
	}

	if (  $es->is_index_created() ) {

		return $es->delete_index( $index_deletion_args );
	}

	return false;
}

/**
 * Recursively include all php files in a directory and subdirectories
 *
 * @param $dir
 * @param int $depth
 * @param int $max_scan_depth
 */
function include_dir( $dir, $depth = 0, $max_scan_depth = 5 ) {

	if ( $depth > $max_scan_depth ) {
		return;
	}

	// require all php files
	$scan = glob( $dir . '/*' );

	foreach ( $scan as $path ) {
		if ( preg_match( '/\.php$/', $path ) ) {
			require_once $path;
		} elseif ( is_dir( $path ) ) {
			include_dir( $path, $depth + 1, $max_scan_depth );
		}
	}
}

/**
 * Reindex all of the supplied types (fires immediately, does trigger a cron)
 *
 * @param $type_names
 */
function reindex_types( $type_names, $flush = true ) {

	foreach ( $type_names as $type_name ) {

		$type = Master::get_instance()->get_type( $type_name );

		if ( $type && ! $flush ) {
			$type->index_all();
		} else if( $type ) {
			$type->reindex_all();
		}
	}
}

/**
 * Resync all of the supplied types (adds missing entries. fires immediately, does trigger a cron)
 *
 * @param $type_names
 */
function resync_types( $type_names ) {

	foreach ( $type_names as $type_name ) {

		$type = Master::get_instance()->get_type( $type_name );

		if ( $type ) {

			$type->index_pending();
		}
	}
}

/**
 * Add a 10 minute schedule to WP Cron
 */
add_filter( 'cron_schedules', function( $intervals ) {

	$intervals['minutes_10'] = array('interval' => 10*60, 'display' => 'Once 10 minutes');

	return $intervals;

} );
