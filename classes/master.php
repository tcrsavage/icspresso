<?php

namespace Icspresso;

use Icspresso\Types\Base;

class Master {

	/**
	 * @var
	 */
	static $instance;

	/**
	 * @var string
	 */
	static $index_cron_name = 'icspresso_update_index_cron';

	/**
	 * @var bool
	 */
	var $is_initialised     = false;

	/**
	 * @var array
	 */
	var $types              = array();

	/**
	 * @param Configuration $configuration
	 */
	public function __construct( Configuration $configuration ) {

		$this->configuration = $configuration;
	}

	/**
	 * @return Master
	 */
	public static function get_instance() {

		if ( ! static::$instance ) {
			static::$instance = new static( get_default_configuration() );
		}

		return static::$instance;
	}

	/**
	 * Initialise the Icspresso type classes
	 *
	 */
	public function initialise() {

		if ( ! $this->is_initialised ) {

			foreach ( $this->get_types() as $type ) {

				$this->set_hooks( $type );
			}

			self::init_cron();
		}

		$this->is_initialised = true;
	}

	/**
	 * Get all Icspresso type class instances
	 *
	 * @return Types\Base[]
	 */
	public function get_types() {

		if ( ! $this->types ) {

			foreach ( $this->configuration->get_active_types() as $class_name ) {

				$class = new $class_name( $this->configuration );

				$this->types[] = $class;
			}
		}

		return $this->types;
	}

	/**
	 * Get a Icspresso type class instance from the type name
	 *
	 * @param $type_name
	 * @return Types\Base|bool
	 */
	public function get_type( $type_name ) {

		foreach ( $this->get_types() as $type ) {

			if ( $type->name === $type_name ) {

				return $type;
			}
		}

		return false;
	}

	/**
	 * Set the hooks of a Icspresso type class
	 *
	 * @param $class
	 */
	protected function set_hooks( Base $class ) {

		if ( ! get_default_configuration()->get_is_indexing_enabled() ) {
			return;
		}

		foreach ( $class->index_hooks as $hook ) {

			add_action( $hook, array( $class, 'index_callback' ), 10, 5 );
		}

		foreach ( $class->delete_hooks as $hook ) {

			add_action( $hook, array( $class, 'delete_callback' ), 10, 5 );
		}

		foreach ( $class->mappable_hooks as $hook => $function ) {

			add_action( $hook, array( $class, $function ), 10, 5 );
		}

		add_action( 'shutdown',  array( $class, 'save_actions' ) );
	}

	/**
	 *
	 */
	protected function init_cron() {

		if ( ! get_default_configuration()->get_is_indexing_enabled() ) {
			return;
		}

		add_action( static::$index_cron_name, array( $this, 'execute_index_cron' ), 10, 5 );

		if ( wp_next_scheduled( static::$index_cron_name ) ) {
			return;
		}

		wp_schedule_event( time(), 'minutes_10', static::$index_cron_name );
	}

	/**
	 *
	 */
	public function execute_index_cron() {

		if ( ! get_default_configuration()->get_is_indexing_enabled() ) {
			return;
		}

		foreach ( self::get_types() as $type ) {

			$type->execute_queued_actions();
		}

	}
}