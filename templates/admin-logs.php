<?php
namespace Icspresso;
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