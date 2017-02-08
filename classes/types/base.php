<?php

namespace Icspresso\Types;
use Icspresso\API;
use Icspresso\Logger;
use Icspresso\Configuration;

/**
 * Class Base
 * @package Icspresso\Types
 */
abstract class Base {

	public $name            = '';
	public $api             = '';
	public $items_per_page  = 100;
	public $index_hooks     = array();
	public $delete_hooks    = array();
	public $mappable_hooks  = array();
	public $queued_actions  = array();

	function __construct( Configuration $configuration ) {

		$this->configuration = $configuration;
	}

	/**
	 * Index callback, to be called when an item is added or edited in the database
	 *
	 * @param $item
	 * @param array $args
	 * @return mixed
	 */
	public abstract function index_callback( $item, $args = array() );

	/**
	 * Delete callback - to be fired when an item is deleted from the database
	 *
	 * @param $item
	 * @param array $args
	 * @return mixed
	 */
	public abstract function delete_callback( $item, $args = array()  );

	/**
	 * Parse an item for indexing (should support being supplied either an ID, or an item object/array
	 *
	 * @param $item
	 * @param array $args
	 * @return mixed
	 */
	public abstract function parse_item_for_index( $item, $args = array() );

	/**
	 * Get items of specific type to index (used when initialising index)
	 *
	 * @param $page
	 * @param $per_page
	 * @return mixed
	 */
	public abstract function get_items( $page, $per_page );

	/**
	 * Get item ids of specific type to index (used when adding pending items to the index)
	 *
	 * @param $page
	 * @param $per_page
	 * @return mixed
	 */
	public abstract function get_items_ids( $page, $per_page );

	/*
	 * Get an integer count of the number of items which can potentially be indexed in the database
	 *
	 * Should serve to return a count which matches the same number of items which can be obtained from use of the get_items method
	 *
	 * @return int
	 */
	/**
	 * @return mixed
	 */
	public abstract function get_items_count();

	/**
	 * Get the mapping array used for mapping the current model type
	 *
	 * @return mixed
	 */
	public function get_mapping() {

		return apply_filters( 'icspresso_mapping_' . $this->name, array() );
	}

	/**
	 * Set the mapping for the current model type
	 *
	 * @return mixed
	 */
	public function set_mapping() {

		if ( ! $this->get_mapping() ) {
			return false;
		}

		return $this->get_api()->map( $this->get_mapping(), array(
			'type' => $this->name,
		) );
	}

	/**
	 * Get the Wrapper, initialised with default index and type pre set
	 *
	 * @return API
	 */
	public function get_api() {

		if ( ! $this->api ) {

			$this->api = new API( $this->configuration );
			$this->api->setType( $this->name );
		}

		return $this->api;
	}

	/**
	 * Add an item to the index
	 *
	 * @param int|object $item
	 */
	public function index_item( $item ) {

		$parsed = $this->parse_item_for_index( $item );
		$parsed = apply_filters( 'icspresso_index_' . $this->name, $parsed, $item, $this );

		if ( ! $parsed ) {
			return;
		}

		if ( ! empty( $parsed['_id'] ) ) {
			$id = $parsed['_id'];
			unset( $parsed['_id'] );
		} else {
			$id = $parsed['ID'];
		}

		$this->get_api()->index( $parsed, $id );
	}

	/**
	 * Delete an item from the index with specified document ID
	 *
	 * @param $item_id
	 */
	public function delete_item( $item_id ) {

		if ( ! $item_id ) {
			return;
		}

		$this->get_api()->delete( $item_id );
	}

	/**
	 * Search for documents of a type in the index
	 *
	 * @param $query
	 * @param array $options
	 * @return array
	 */
	public function search( $query, $options = array() ) {

		return $this->get_api()->search( $query, $options );
	}

	/**
	 * Bulk index items, this function should be passed an array of ids or objects
	 *
	 * @param array $items[int|object]
	 * @param array $args ['bulk']
	 */
	public function index_items( $items, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'bulk' => false,
		) );

		if ( $args['bulk'] ) {

			$this->get_api()->begin();
		}

		foreach ( $items as $item ) {

			$this->index_item( $item );
		}

		if ( $args['bulk'] ) {

			$this->get_api()->commit();
		}

	}

	/**
	 * Bulk delete items. this function should be passed an array of ids (index document id)
	 *
	 * @param array $items
	 * @param array $args ['bulk']
	 */
	public function delete_items( $items, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'bulk' => false,
		) );

		if ( $args['bulk'] ) {

			$this->get_api()->begin();
		}

		foreach ( $items as $item ) {

			$this->delete_item( $item );
		}

		if ( $args['bulk'] ) {

			$this->get_api()->commit();
		}
	}

	/**
	 * Index all the items for the specified type
	 */
	public function index_all() {

		$has_items = true;
		$page = 1;

		$this->set_is_doing_full_index( true );

		while ( $has_items ) {

			$this->stop_the_insanity();

			$items = $this->get_items( $page, $this->items_per_page );

			if ( $items ) {
				$this->index_items( $items, array( 'bulk' => true ) );
			} else {
				$has_items = false;
			}

			$page++;
		}

		$this->set_is_doing_full_index( false );
	}

	/**
	 * Flush and reindex all items in the index
	 */
	function reindex_all() {

		$this->delete_all_indexed_items();
		$this->set_mapping();
		$this->index_all();
	}

	/**
	 * Index all items which are pending redindex or insertion
	 *
	 * This method is called every 10minues via WP_Cron by default for each model type
	 *
	 */
	public function index_pending() {

		$this->set_is_doing_full_index( true );

		$has_items = true;
		$page = 1;
		$total_items = $this->get_items_count();

		while ( $has_items ) {

			$this->stop_the_insanity();

			$items = $this->get_items_ids( $page, $this->items_per_page );
			$items = apply_filters( 'icspresso_filter_items_ids_' . $this->name, $items );

			$r = $this->search( array(
				'fields' => array(),
				'query'  => array(
					'ids'  => array( 'values' => $items ),
				),
				'size'   => $this->items_per_page,
				'from'   => $this->items_per_page * ( $page - 1 ),
			) );

			if ( ! empty( $r['hits']['total'] ) ) {
				$hits_count = $r['hits']['total'];
			} else {
				$hits_count = 0;
			}

			$cur_count = $this->get_api()->request( '_count' );
			$cur_count = ! empty( $cur_count['count'] ) ? $cur_count['count'] : 0;

			if ( $hits_count < count( $items ) ) {
				$this->index_items( $this->get_items( $page, $this->items_per_page ), array( 'bulk' => true ) );
			}

			if ( ! $items || ( $cur_count >= $total_items ) ) {
				$has_items = false;
			}

			$page++;
		}// End while().

		$this->set_is_doing_full_index( false );
	}

	/**
	 * Queue an action for execution on php shutdown - e.g. index post item after 'save_post' hook has been fired
	 *
	 * @param $action
	 * @param $identifier
	 * @param array $args
	 */
	function add_action( $action, $identifier, $args = array() ) {

		//keep actions in order of when they were last set
		if ( isset( $this->queued_actions[ $identifier ][ $action ] ) ) {
			unset( $this->queued_actions[ $identifier ][ $action ] );
		}

		$this->queued_actions[ (string) $identifier ][ $action ] = $args;
	}

	/**
	 * Get all indexing actions queued by the current thread
	 *
	 * @return array
	 */
	function get_actions() {

		return $this->queued_actions;
	}

	/**
	 * Acquire a save lock to update the global actions queue with those set in the current thread
	 *
	 * @param string $action
	 * @return bool
	 */
	function acquire_lock( $action ) {

		$attempts = 0;

		//Wait until other threads have finished saving their queued items (failsafe)
		while ( ! wp_cache_add( 'icspresso_queued_actions_lock_' . $this->name . '_' . $action, '1', '', 60 ) && $attempts < 10 ) {
			$attempts++;
			time_nanosleep( 0, 500000000 );
		}

		return $attempts < 10 ? true : false;
	}

	/**
	 * Clear the save lock after global actions have been updated
	 *
	 * @param string $action
	 */
	function clear_lock( $action ) {

		wp_cache_delete( 'icspresso_queued_actions_lock_' . $this->name . '_' . $action );
	}

	/**
	 * Get all actions that have been queued, e.g. index items/delete items
	 *
	 */
	function save_actions() {

		//no actions to save
		if ( ! $this->queued_actions ) {
			return;
		}

		if ( ! $this->acquire_lock( 'save_actions' ) ) {
			return;
		}

		$saved  = $this->get_saved_actions();
		$all    = array_replace_recursive( $saved, $this->queued_actions );

		if ( count( $all ) > 10000 ) {

			Logger::save_log( array(
				'timestamp'      => time(),
				'type'           => 'warning',
				'index'          => $this->configuration->get_index_name(),
				'document_type'  => $this->name,
				'caller'         => 'save_queued_actions',
				'args'           => '-',
				'message'        => 'Saved actions buffer overflow. Too many actions have been saved for later syncing. (' . count( $all ) . ' items)',
			) );

			$all = array_slice( $all, -10000, 10000, true );
		}

		$this->clear_saved_actions();
		add_option( 'icspresso_queued_actions_' . $this->name, $all, '', 'no' );

		$this->clear_lock( 'save_actions' );
	}

	/**
	 * Get the array of global indexing actions which should be performed
	 *
	 * @return array
	 */
	function get_saved_actions() {

		return get_option( 'icspresso_queued_actions_' . $this->name, array() );
	}

	/**
	 * Clear the saved indexing actions
	 *
	 */
	function clear_saved_actions() {

		delete_option( 'icspresso_queued_actions_' . $this->name );
	}

	/**
	 * Get the hook name for the queued actions execution cron
	 *
	 * @return string
	 */
	function get_execute_cron_hook() {

		return 'icspresso_execute_queued_actions_cron_' . $this->name;
	}

	/**
	 * Find all queued actions and execute them, save the actions for later if the ES server is not available
	 */
	function execute_queued_actions() {

		if ( ! $this->acquire_lock( 'execute_queued_actions' ) ) {
			return;
		}

		$actions = $this->get_saved_actions();
		$this->clear_saved_actions();

		if ( ! $actions ) {

			$this->clear_lock( 'execute_queued_actions' );
			return;
		}

		///If we can't get a connection at the moment, save the queued actions for processing later
		if ( ! $this->get_api()->is_connection_available() || ! $this->get_api()->is_index_created() ) {

			Logger::save_log( array(
				'timestamp'      => time(),
				'message'        => 'Failed to execute syncing actions for ' . count( $actions ) . ' items.',
				'data'           => array( 'document_type' => $this->name, 'queued_actions' => $actions ),
			) );

			$this->queued_actions = $actions;
			$this->save_actions();

		//else execute the actions now
		} else {

			//Begin a bulk transaction
			$this->get_api()->begin();

			foreach ( $actions as $identifier => $object ) {
				foreach ( $object as $action => $args ) {
					$this->$action( $identifier, $args );
				}
			}

			//Finish the bulk transaction
			$this->get_api()->commit();
		}

		$this->clear_lock( 'execute_queued_actions' );
	}

	/**
	 * Set a flag when we are performing a full index
	 *
	 * @param $bool
	 */
	function set_is_doing_full_index( $bool ) {

		if ( $bool ) {
			update_option( 'icspresso_' . $this->name . '_is_doing_full_index', time() );
		} else {

			delete_option( 'icspresso_' . $this->name . '_is_doing_full_index' );
		}

	}

	/**
	 * Check if we are performing a full index
	 *
	 * @return bool
	 */
	function get_is_doing_full_index() {

		$val = get_option( 'icspresso_' . $this->name . '_is_doing_full_index', 0 );

		return strtotime( '-30 minutes', time() ) < $val;
	}

	/**
	 * Get the status of the index
	 *
	 * @return array
	 */
	function get_status() {

		$response = array();

		$count = $this->get_api()->request( '_count' );

		if ( empty( $count['error'] ) ) {
			$response['indexed_count'] = $count['count'];
		} else {
			$response['error'] = $count['error'];
			$response['indexed_count'] = 0;
		}

		$response['database_count'] = $this->get_items_count();

		$response['is_doing_full_index'] = $this->get_is_doing_full_index();

		return $response;

	}

	/**
	 * Delete all items from the index
	 */
	public function delete_all_indexed_items() {

		$this->get_api()->request( array( '/', $this->configuration->get_index_name(), $this->name ), 'DELETE' );
	}

	/**
	 * Internal function for filtering items before they are passed to elasticsearch for indexing
	 *
	 * @param $item
	 * @return array
	 */
	protected function filter_item( $item ) {

		return apply_filters( 'icpresso_filter_item_' . $this->name, $item );
	}

	/**
	 * Flush the local object cache if it exists
	 *
	 * Vastly reduces memory limit issues when running a full index on large databases
	 */
	protected function stop_the_insanity() {

		global $wpdb, $wp_object_cache;

		$wpdb->queries = array(); // or define( 'WP_IMPORTING', true );

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		$wp_object_cache->group_ops = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache = array();

		if ( is_callable( $wp_object_cache, '__remoteset' ) ) {
			$wp_object_cache->__remoteset(); // important
		}
	}
}
