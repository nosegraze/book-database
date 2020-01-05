<?php
/**
 * Analytics: Reading
 *
 * @package   book-database
 * @copyright Copyright (c) 2020, Ashley Gibson
 * @license   GPL2+
 */

namespace Book_Database\Analytics;

/**
 * Reading tab
 */
function reading() {
	?>
	<h2><?php _e( 'Reading', 'book-database' ); ?></h2>

	<div class="bdb-flexbox-container">
		<section class="bdb-analytics-block bdb-dataset-type-table bdb-flexbox-half" data-dataset="Most_Read_Genres">
			<h3><?php _e( 'Most Read Genres', 'book-database' ); ?></h3>

			<table class="wp-list-table widefat fixed striped">
				<thead>
				<tr>
					<th class="column-primary"><?php _e( 'Genre', 'book-database' ); ?></th>
					<th><?php _e( 'Number of Books Read', 'book-database' ); ?></th>
				</tr>
				</thead>
				<tbody class="bdb-dataset-value">
				<tr>
					<td colspan="2"><?php _e( 'Loading...', 'book-database' ); ?></td>
				</tr>
				</tbody>
			</table>

			<script type="text/html" id="tmpl-bdb-analytics-most-read-genres" class="bdb-analytics-template">
				<?php require_once BDB_DIR . 'includes/admin/analytics/templates/tmpl-most-read-genres.php'; ?>
			</script>

			<script type="text/html" id="tmpl-bdb-analytics-most-read-genres-none" class="bdb-analytics-template-none">
				<?php require_once BDB_DIR . 'includes/admin/analytics/templates/tmpl-most-read-genres-none.php'; ?>
			</script>
		</section>

		<section class="bdb-analytics-block bdb-dataset-type-table bdb-flexbox-half" data-dataset="Pages_Breakdown" data-canvas="bdb-dataset-pages-breakdown">
			<h3><?php _e( 'Pages Breakdown', 'book-database' ); ?></h3>
			<div>
				<div id="bdb-dataset-pages-breakdown"></div>
			</div>
		</section>
	</div>
	<?php
}

add_action( 'book-database/analytics/reading', __NAMESPACE__ . '\reading' );