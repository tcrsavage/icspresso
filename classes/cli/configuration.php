<?php

namespace Icspresso\CLI;

use Icspresso;

class Configuration extends \WP_CLI_Command {

	/**
	 * Get or set the configuration's server 'host' value
	 *
	 * @subcommand host
	 * @synopsis <method>
	 *
	 */
	public function host( $args, $args_assoc ) {

		$this->call_standard_method( 'host', 'sanitize_text_field', $args, $args_assoc );
	}

	/**
	 * Get or set the configuration's server 'port' value
	 *
	 * @subcommand host
	 * @synopsis <method>
	 *
	 */
	public function port( $args, $args_assoc ) {

		$this->call_standard_method( 'port', 'absint', $args, $args_assoc );
	}

	/**
	 * Get or set the configuration's server 'protocol' value
	 *
	 * @subcommand host
	 * @synopsis <method>
	 *
	 */
	public function protocol( $args, $args_assoc ) {

		$this->call_standard_method( 'protocol', 'sanitize_text_field', $args, $args_assoc );
	}

	/**
	 * Get or set the configuration's server 'index_name' value
	 *
	 * @subcommand index-name
	 * @synopsis <method>
	 *
	 */
	public function index_name( $args, $args_assoc ) {

		$this->call_standard_method( 'index_name', 'sanitize_text_field', $args, $args_assoc );
	}

	/**
	 * Get or set the configuration's server connection 'timeout' value
	 *
	 * @subcommand timeout
	 * @synopsis <method>
	 *
	 */
	public function timeout( $args, $args_assoc ) {

		$this->call_standard_method( 'timeout', 'absint', $args, $args_assoc );
	}

	/**
	 * Get or set the configuration's 'max_logs' value
	 *
	 * @subcommand host
	 * @synopsis <method>
	 *
	 */
	public function max_logs( $args, $args_assoc ) {

		$this->call_standard_method( 'max_logs', 'absint', $args, $args_assoc );
	}

	/**
	 * Get or set the configuration's 'is_logging_enabled' value
	 *
	 * @subcommand host
	 * @synopsis <method>
	 *
	 */
	public function is_logging_enabled( $args, $args_assoc ) {

		$this->call_standard_method( 'is_logging_enabled', array( $this, 'to_bool' ), $args, $args_assoc );
	}

	/**
	 * Get or set the configuration's 'is_indexing_enabled' value
	 *
	 * @subcommand host
	 * @synopsis <method>
	 *
	 */
	public function is_indexing_enabled( $args, $args_assoc ) {

		$this->call_standard_method( 'is_indexing_enabled', array( $this, 'to_bool' ), $args, $args_assoc );
	}

	protected function call_standard_method( $setting, $sanitize_callback, $args, $args_assoc ) {

		$method     = $args[0];
		$method_get = 'get_' . $setting;
		$method_set = 'set_' . $setting;

		$arg = call_user_func( $sanitize_callback, $args[1] );

		switch ( $method ) {

			case 'get' :
				$this->line( Icspresso\Master::get_instance()->configuration->{$method_get}() );
				break;

			case 'set' :

				if ( ! isset( $args[1] ) ) {
					$this->error( 'Missing 2nd param {host}' );
				}

				Icspresso\Master::get_instance()->configuration->{$method_set}( $arg );

				$val = Icspresso\Master::get_instance()->configuration->{$method_get}();

				$val = ( $val === null   || $val === false ) ? 'false' : $val;
				$val = ( $val === '1'    || $val === true  ) ? 'true'  : $val;

				$this->success( $setting . ' updated: ' .  $val );

				break;

			default:
				\WP_CLI::error( 'Unrecognised method: ' . $method . '. Please use "get" or "set"' );
		}

		$this->line( 'Filters and defines override settings stored in the database' );
	}

	protected function to_bool( $var ) {

		if ( $var === 'false' || $var === '0' || ! $var ) {
			return false;
		}

		return true;
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
