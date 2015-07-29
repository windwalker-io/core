<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

use Windwalker\Core\Pagination\PaginationResult;
use Windwalker\Core\Router\Router;
use Windwalker\Data\Data;

/**
 * @var Data             $data
 * @var PaginationResult $pagination
 * @var string           $route
 */
$pagination = $data->pagination;
$route = $data->route;
?>
<ul class="pagination windwalker-pagination">
	<?php if ($pagination->getFirst()): ?>
		<li>
			<a href="<?php echo Router::buildHtml($route, array('page' => $pagination->getFirst())); ?>">
				First
			</a>
		</li>
	<?php endif; ?>

	<?php if ($pagination->getPrevious()): ?>
		<li>
			<a href="<?php echo Router::buildHtml($route, array('page' => $pagination->getPrevious())); ?>">
				Previous
			</a>
		</li>
	<?php endif; ?>

	<?php if ($pagination->getLess()): ?>
		<li>
			<a href="<?php echo Router::buildHtml($route, array('page' => $pagination->getLess())); ?>">
				Less
			</a>
		</li>
	<?php endif; ?>

	<?php foreach ($pagination->getPages() as $k => $page): ?>
		<?php $active = ($page == 'current') ? 'active' : ''; ?>
		<li class="<?php echo $active; ?>">
			<?php if (!$active): ?>
				<a href="<?php echo Router::buildHtml($route, array('page' => $k)); ?>">
					<?php echo $k; ?>
				</a>
			<?php else: ?>
				<a href="javascript:void(0);">
					<?php echo $k; ?>
				</a>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>

	<?php if ($pagination->getMore()): ?>
		<li>
			<a href="<?php echo Router::buildHtml($route, array('page' => $pagination->getMore())); ?>">
				More
			</a>
		</li>
	<?php endif; ?>

	<?php if ($pagination->getNext()): ?>
		<li>
			<a href="<?php echo Router::buildHtml($route, array('page' => $pagination->getNext())); ?>">
				Next
			</a>
		</li>
	<?php endif; ?>

	<?php if ($pagination->getLast()): ?>
		<li>
			<a href="<?php echo Router::buildHtml($route, array('page' => $pagination->getLast())); ?>">
				Last
			</a>
		</li>
	<?php endif; ?>
</ul>
