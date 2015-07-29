<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
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