<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

$pagination = $data->pagination;

?>
<ul>
	<?php foreach ($pagination->getAll() as $k => $page): ?>
	<li>
		Page: <?php echo $k; ?> - Type: <?php echo $page; ?>
	</li>
	<?php endforeach; ?>
</ul>