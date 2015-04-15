<?php

namespace Icspresso;

add_action( 'admin_menu', '\\Icspresso\\admin_screen' );

/**
 * Add a submenu page to settings for Icspresso
 */
function admin_screen() {

	$hook_settings = add_menu_page( 'Icspresso Elasticsearch', 'ElasticSearch', 'manage_options', 'icspresso-settings', function() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>General Settings</h2>

			<form method="post">
				<?php wp_nonce_field( 'icspresso_settings', 'icspresso_settings' ); ?>

				<table class="form-table">
					<tbody>
					<tr valign="top">
						<th scope="row"><label for="icspresso_host">Elastic Search Host</label></th>
						<td><input name="icspresso_host" type="text" id="icspresso_host" value="<?php echo Configuration::get_default_host(); ?>" placeholder="10.1.1.5" class="regular-text"></td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="icspresso_port">Elastic Search Port</label></th>
						<td><input name="icspresso_port" type="text" id="icspresso_port" value="<?php echo Configuration::get_default_port(); ?>" placeholder="9200" class="regular-text"></td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="icspresso_protocol">Elastic Search Protocol</label></th>
						<td>
							<select	id="icspresso_protocol" name="icspresso_protocol">
								<?php foreach ( Configuration::get_supported_protocols() as $protocol => $label ) : ?>
									<option value="<?php echo $protocol; ?>" <?php selected( $protocol, Configuration::get_default_protocol() ); ?>><?php echo $label; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="icspresso_is_enabled">Enable Elastic Search Indexing</label></th>
						<td>
							<input type="hidden" name="icspresso_is_enabled" value="0" />
							<input name="icspresso_is_enabled" type="checkbox" id="icspresso_is_enabled" <?php checked( Configuration::get_is_indexing_enabled() ); ?> value="1">
						</td>
					</tr>

					<?php if ( Logger::count_logs() ) : ?>

						<tr valign="top">
							<th scope="row"><label for="icspresso_clear_logs">Clear Logs</label></th>
							<td>
								<input type="hidden" name="icspresso_clear_logs" value="0" />
								<input name="icspresso_clear_logs" type="checkbox" id="icspresso_clear_logs" value="1">
							</td>
						</tr>

					<?php endif; ?>

					<tr valign="top">
						<?php $status = Wrapper::get_instance()->is_connection_available( array( 'log' => false ) ); ?>
						<th scope="row"><label for="">Status</label></th>
						<td><span style="color: <?php echo ( $status ) ? 'green' : 'red'; ?>"><?php echo ( $status ) ? 'OK' : 'Connection failed'; ?></span></td>
					</tr>

					</tbody>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
			</form>
		</div>
	<?php
	} );

	$hook_indexing = add_submenu_page( 'icspresso-settings', 'Indexing', 'Indexing', 'manage_options', 'icspresso-indexing', function() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Indexing</h2>

			<?php wp_nonce_field( 'icspresso_settings', 'icspresso_settings' ); ?>

			<?php foreach( Type_Manager::get_types() as $type ) : ?>

				<h3><?php echo ucwords( $type->name ) . 's' ; ?></h3>

				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="icspresso_reindex_<?php echo $type->name; ?>">Status</label></th>
							<td>
								<div class="icspresso-status-wrapper">
									<div class="icspresso-status-message icspresso-status-message-<?php echo $type->name; ?>">Fetching...</div>
									<div class="icspresso-status icspresso-status-<?php echo $type->name; ?>" data-type-name="<?php echo $type->name; ?>" ></div>
								</div>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label for="icspresso_reindex_<?php echo $type->name; ?>">Indexing</label></th>
							<td>

								<?php if ( Configuration::get_is_indexing_enabled() ) : ?>

									<?php $next_scheduled = ( time() < wp_next_scheduled( Type_Manager::$index_cron_name ) ) ? human_time_diff( time(), wp_next_scheduled( Type_Manager::$index_cron_name ) ) : 'now'; ?>

									<div class="icspresso-automatic-indexing-information">
										<div>Items pending sync: <strong><?php echo count( $type->get_saved_actions() ); ?></strong></div>
										<div>Next automatic sync: <strong><?php echo $next_scheduled ?></strong></div>
									</div>

								<?php endif; ?>

								<input type="button" id="icspresso_reindex_<?php echo $type->name; ?>" data-type-name="<?php echo $type->name; ?>" class="button icspresso-reindex-submit" value="Reindex" />
								<input type="button" id="icspresso_resync_<?php echo $type->name; ?>" data-type-name="<?php echo $type->name; ?>" class="button icspresso-resync-submit" value="Resync" />
							</td>
						</tr>
					</tbody>
				</table>

			<?php endforeach; ?>

		</div>
		<?php
	} );

	$hook_logs = add_submenu_page( 'icspresso-settings', 'Logs', 'Logs', 'manage_options', 'icspresso-logs', function() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Logs</h2>

			<?php if ( Logger::count_logs() ) : ?>

				<?php $page = ( ! empty( $_GET['log_page'] ) ) ? intval( $_GET['log_page'] ) : 1; ?>
					<table class="widefat icspresso-log-table">
						<thead>
						<tr>
							<th>ID</th>
							<th>Type</th>
							<th>Date</th>
							<th>Message</th>
							<th>Data</th>
							<th>Expand</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( Logger::get_paginated_logs( $page, 20 ) as $entry_number => $log_item ) : ?>
							<tr>
								<td class="td-id"><div><pre><?php echo $entry_number; ?></pre></div></td>
								<td class="td-type"><div><pre><?php echo $log_item['type']; ?></pre></div></td>
								<td class="td-date"><div><pre><?php echo date( 'Y-m-d H:i:s', $log_item['timestamp'] ); ?></pre></div></td>
								<td class="td-message"><div><pre><?php print_r( $log_item['message'] )?></pre></div></td>
								<td class="td-data"><div><pre><?php print_r( $log_item['data'] )?></pre></div></td>

								<td class="expand"><div class="cell">+</div></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php if ( ( $log_count = Logger::count_logs() ) > 20 ) : ?>
						<div class="icspresso-log-table-pagination">
							<span>Page</span>
							<?php for ( $i = 1; $i < ( ( $log_count + 20 ) / 20 ); $i++ ) : ?>
								<a href="<?php echo add_query_arg( 'log_page', $i ); ?>"><?php echo $i; ?></a>
							<?php endfor; ?>
						</div>
					<?php endif; ?>

			<?php else : ?>

				<p>No Logs to display</p>

			<?php endif; ?>

			<p class="alignleft"><span class="description">Up to <?php echo Logger::get_max_logs(); ?> log entries can be stored, older logs will be automatically deleted</span></p>

		</div>
	<?php
	} );

	add_action( 'load-'. $hook_settings, '\\Icspresso\\init_elastic_search_index', 9 );
	add_action( 'load-'. $hook_settings, '\\Icspresso\\process_admin_screen_form_submission' );
	add_action( 'load-'. $hook_settings, '\\Icspresso\\enqueue_admin_assets' );

	add_action( 'load-'. $hook_logs, '\\Icspresso\\enqueue_admin_assets' );
	add_action( 'load-'. $hook_indexing, '\\Icspresso\\enqueue_admin_assets' );
}

/**
 * Capture form submissions from the Icspresso settings page
 */
function process_admin_screen_form_submission() {

	if ( ! isset( $_POST['submit'] ) || ! wp_verify_nonce( $_POST['icspresso_settings'], 'icspresso_settings' ) )
		return;

	if ( isset( $_POST['icspresso_host'] ) )
		Configuration::set_default_host( str_replace( 'http://', '', sanitize_text_field( $_POST['icspresso_host'] ) ) );

	if ( isset( $_POST['icspresso_port'] ) )
		Configuration::set_default_port( sanitize_text_field( $_POST['icspresso_port'] ) );

	if ( isset( $_POST['icspresso_protocol'] ) && array_key_exists( sanitize_text_field( $_POST['icspresso_protocol'] ), Configuration::get_supported_protocols() ) ) {
		Configuration::set_default_protocol( sanitize_text_field( $_POST['icspresso_protocol'] ) );
	}

	if ( isset( $_POST['icspresso_is_enabled'] ) ) {

		Configuration::set_is_indexing_enabled( (bool) sanitize_text_field( $_POST['icspresso_is_enabled'] ) );
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
	$type      = Type_Manager::get_type( $type_name );

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

	Type_Manager::get_type( $type_name )->set_is_doing_full_index( true );
	Type_Manager::get_type( $type_name )->delete_all_indexed_items();

	wp_schedule_single_event( time(), 'icspresso_reindex_types_cron', array( 'type_name' => $type_name, 'timestamp' => time() ) );

	exit;

} );

//Capture ajax request to refresh a type in the elasticsearch index
add_action( 'wp_ajax_icspresso_resync_index', function() {

	if ( ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'icspresso_settings' ) )
		exit;

	$type_name = sanitize_text_field( $_POST['type_name'] );

	Type_Manager::get_type( $type_name )->set_is_doing_full_index( true );

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