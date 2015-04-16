<?php
namespace Icspresso;
?>

<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Indexing</h2>

	<?php wp_nonce_field( 'icspresso_settings', 'icspresso_settings' ); ?>

	<?php foreach( Master::get_instance()->get_types() as $type ) : ?>

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

					<?php if ( get_default_configuration()->get_is_indexing_enabled() ) : ?>

						<?php $next_scheduled = ( time() < wp_next_scheduled( Master::$index_cron_name ) ) ? human_time_diff( time(), wp_next_scheduled( Master::$index_cron_name ) ) : 'now'; ?>

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