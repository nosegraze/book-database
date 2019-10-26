<?php
/**
 * Analytics Template: Pages Breakdown
 *
 * @package   nosegraze
 * @copyright Copyright (c) 2019, Ashley Gibson
 * @license   GPL2+
 */

namespace Book_Database;

?>
<tr>
	<td data-th="<?php esc_attr_e('Pages', 'book-database'); ?>">
		<# if ( null == data.page_range ) { #>
		&ndash;
		<# } else { #>
		{{ data.page_range }}
		<# } #>
	</td>
	<td data-th="<?php esc_attr_e('Number of Books', 'book-database'); ?>">
		{{ data.number_books }}
	</td>
</tr>
