<?php
namespace Icspresso;
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
				<td><input name="icspresso_host" type="text" id="icspresso_host" value="<?php echo get_default_configuration()->get_host(); ?>" placeholder="10.1.1.5" class="regular-text"></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="icspresso_port">Elastic Search Port</label></th>
				<td><input name="icspresso_port" type="text" id="icspresso_port" value="<?php echo get_default_configuration()->get_port(); ?>" placeholder="9200" class="regular-text"></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="icspresso_is_enabled">Enable Elastic Search Indexing</label></th>
				<td>
					<input type="hidden" name="icspresso_is_enabled" value="0" />
					<input name="icspresso_is_enabled" type="checkbox" id="icspresso_is_enabled" <?php checked( get_default_configuration()->get_is_indexing_enabled() ); ?> value="1">
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
				<?php $api    = new API( get_default_configuration() ); ?>
				<?php $status = $api->is_connection_available( array( 'log' => false ) ); ?>
				<th scope="row"><label for="">Status</label></th>
				<td><span style="color: <?php echo ( $status ) ? 'green' : 'red'; ?>"><?php echo ( $status ) ? 'OK' : 'Connection failed'; ?></span></td>
			</tr>

			</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
	</form>
</div>