<?php

namespace Icspresso;

add_action( 'admin_menu', '\\Icspresso\\admin_screen' );

/**
 * Add a submenu page to settings for Icspresso
 */
function admin_screen() {

	$hook_settings = add_menu_page( 'Icspresso Elasticsearch', 'ElasticSearch', 'manage_options', 'icspresso-settings', function() {

		load_template( 'admin-general' );
	} );

	$hook_indexing = add_submenu_page( 'icspresso-settings', 'Indexing', 'Indexing', 'manage_options', 'icspresso-indexing', function() {

		load_template( 'admin-indexing' );
	} );

	$hook_logs = add_submenu_page( 'icspresso-settings', 'Logs', 'Logs', 'manage_options', 'icspresso-logs', function() {

		load_template( 'admin-logs' );
	} );

	add_action( 'load-'. $hook_settings, '\\Icspresso\\init_elastic_search_index', 9 );
	add_action( 'load-'. $hook_settings, '\\Icspresso\\process_admin_screen_form_submission' );
	add_action( 'load-'. $hook_settings, '\\Icspresso\\enqueue_admin_assets' );

	add_action( 'load-'. $hook_logs,     '\\Icspresso\\enqueue_admin_assets' );
	add_action( 'load-'. $hook_indexing, '\\Icspresso\\enqueue_admin_assets' );
}

/**
 * Capture form submissions from the Icspresso settings page
 */
function process_admin_screen_form_submission() {

	if ( ! isset( $_POST['submit'] ) || ! wp_verify_nonce( $_POST['icspresso_settings'], 'icspresso_settings' ) )
		return;

	if ( isset( $_POST['icspresso_host'] ) )
		get_default_configuration()->set_host( str_replace( 'http://', '', sanitize_text_field( $_POST['icspresso_host'] ) ) );

	if ( isset( $_POST['icspresso_port'] ) )
		get_default_configuration()->set_port( sanitize_text_field( $_POST['icspresso_port'] ) );

	if ( isset( $_POST['icspresso_is_enabled'] ) ) {

		get_default_configuration()->set_is_indexing_enabled( (bool) sanitize_text_field( $_POST['icspresso_is_enabled'] ) );
	}

	if ( ! empty( $_POST['icspresso_clear_logs'] ) ) {
		Logger::set_logs( array() );
	}

	wp_redirect( add_query_arg( 'updated', '1' ) );

	exit;
}

/**
 * Enqueue scripts and styles for the Icspresso settings page
 */
function enqueue_admin_assets()  {

	wp_enqueue_script( 'icspresso-admin-scripts', plugin_dir_url( __FILE__ ) . 'assets/admin-scripts.js', array( 'jquery' ), false, true );
	wp_enqueue_style( 'icspresso-admin-scripts', plugin_dir_url( __FILE__ ) . 'assets/admin-styles.css' );

};

add_action( 'wp_ajax_icspresso_get_type_status', function() {

	if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'icspresso_settings' ) )
		exit;

	$type_name = sanitize_text_field( $_POST['type_name'] );
	$type      = Master::get_instance()->get_type( $type_name );

	if ( $type ) {
		echo json_encode( $type->get_status() );
	}

	exit;
} );

//Capture ajax request to refresh a type in the elasticsearch index
add_action( 'wp_ajax_icspresso_init_index', function() {

	if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'icspresso_settings' ) ) {
		exit;
	}

	$type_name = sanitize_text_field( $_POST['type_name'] );

	Master::get_instance()->get_type( $type_name )->set_is_doing_full_index( true );
	Master::get_instance()->get_type( $type_name )->delete_all_indexed_items();

	wp_schedule_single_event( time(), 'icspresso_reindex_types_cron', array( 'type_name' => $type_name, 'timestamp' => time() ) );

	exit;

} );

//Capture ajax request to refresh a type in the elasticsearch index
add_action( 'wp_ajax_icspresso_resync_index', function() {

	if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'icspresso_settings' ) )
		exit;

	$type_name = sanitize_text_field( $_POST['type_name'] );

	Master::get_instance()->get_type( $type_name )->set_is_doing_full_index( true );

	wp_schedule_single_event( time(), 'icspresso_resync_types_cron', array( 'type_name' => $type_name, 'timestamp' => time() ) );

	exit;

} );

add_action( 'icspresso_reindex_types_cron', function( $type_name ) {

	// This can take a long time and consume a lot of memory
	if ( ini_get( 'max_execution_time' ) < 3600 ) {

		set_time_limit( 3600 );
	}

	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );

	reindex_types( array( $type_name ) );
} );

add_action( 'icspresso_resync_types_cron', function( $type_name ) {

	// This can take a long time and consume a lot of memory
	if ( ini_get( 'max_execution_time' ) < 3600 ) {

		set_time_limit( 3600 );
	}

	@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );

	resync_types( array( $type_name ) );
} );