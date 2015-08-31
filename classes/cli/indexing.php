<?php

namespace Icspresso\CLI;

use Icspresso;

class Server extends \WP_CLI_Command {

	/**
	 * Initialise the ES index
	 *
	 * @subcommand init-index
	 *
	 *  @synopsis [--force_init]
	 */
	public function init_index( $args, $args_assoc ) {

		$args_assoc = wp_parse_args( $args_assoc, array(
			'force_init' => false
		) );

		$api        = \Icspresso\Master::get_instance()->api;
		$is_created = $api->is_index_created();

		if ( $is_created && ! $args_assoc['force_init'] ) {
			$this->error( 'The index has already been created use --force_init to reinitialise the index' );
		}

		if ( $is_created ) {
			$api->delete_index();
		}

		$api->create_index();

		$this->success( 'Index initialised.' );
	}

	/**
	 * Reindex all items of specified types
	 *
	 * Types can be supplied in a comma separated list "post,comment,user,term"
	 *
	 * @subcommand reindex
	 * @synopsis [--max_memory_limit] [--types]
	 *
	 */
	public function reindex( $args, $args_assoc ) {

		$args_assoc = wp_parse_args( $args_assoc, array(
			'max_memory_limit' =>  apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ),
			'types'            => implode( ',', array_keys( Icspresso\get_default_configuration()->get_active_types() ) )
		) );

		@ini_set( 'memory_limit', $args_assoc['max_memory_limit'] );

		$types = explode( ',', str_replace( ' ', '', $args_assoc['types'] ) );

		foreach( $types as $type_name ) {

			$type = \Icspresso\Master::get_instance()->get_type( $type_name );

			if ( ! $type ) {
				$this->line( 'Could not find type: ' . $type_name );
				continue;
			} else {
				$this->line( 'Indexing type: ' . $type_name . ' (' . $type->get_items_count() . ' items)' );
			}

			$type->index_all();

			$this->success( 'Indexing for type: ' . $type_name . ' complete.' );
		}
	}

	protected function verify_connection() {

		$api = \Icspresso\Master::get_instance()->api;

		if ( ! $api->is_connection_available() ) {
			$this->error( 'Server connection is not available, please check configuration and server status.' );
		}

	}

	protected function verify_index() {

		$api = \Icspresso\Master::get_instance()->api;

		$this->verify_connection();

		if ( ! $api->is_index_created() ) {
			$this->error( 'Index has not yet been created, please create the index.' );
		}
	}

	protected function error( $error ) {

		\WP_CLI::error( $error );
	}

	protected function line( $line ) {

		\WP_CLI::line( $line );
	}

	protected function success( $success ) {

		\WP_CLI::success( $success );
	}

}
