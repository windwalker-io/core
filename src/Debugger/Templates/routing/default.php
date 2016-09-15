<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Debugger\Html\BootstrapKeyValueGrid;
use Windwalker\Dom\HtmlElement;
use Windwalker\Profiler\Point\Collector;
use Windwalker\Utilities\ArrayHelper;

$this->extend('_global.html');

/**
 * @var  Collector  $collector
 */
?>

<?php $this->block('page_title') ?>Routing<?php $this->endblock(); ?>

<?php $this->block('content') ?>

<h2>Routing Information</h2>

<?php
echo BootstrapKeyValueGrid::create()
	->addHeader()
	->addItem('Request Method', $collector['system.method.http'])
	->addItem('Route Matcher', new HtmlElement('code', $collector['routing.matcher']))
	->addItem('Route Number', count($routes))
	->addItem('Matched Route', $matchedRoute->name)
	->addItem('Package Name', $collector['package.name'])
	->addItem('Package Class', new HtmlElement('code', $collector['package.class']))
	->addItem('Controller', new HtmlElement('code', $controller))
	->addItem('Task', new HtmlElement('code', $collector['controller.task']));
?>

<br /><br />

<h2>Uri Information</h2>

<?php

if ($collector['system.uri'])
{
	echo BootstrapKeyValueGrid::create()
		->addHeader()
		->configure(
			ArrayHelper::flatten($collector['system.uri']),
			function (BootstrapKeyValueGrid $grid, $key, $value)
			{
				$grid->addItem(new HtmlElement('code', $key), $this->escape($value));
			}
		);
}
else
{
	echo 'No data';
}

?>

<br /><br />

<h2>All Routes</h2>

<table class="table table-bordered">
<thead>
<tr>
	<th>Package</th>
	<th>Route Name</th>
	<th>Pattern</th>
	<th>Methods</th>
	<th>Controller</th>
</tr>
</thead>
<tbody>
<?php foreach ($routes as $route): ?>

<tr class="<?php echo $route->matched ? 'success' : ''; ?>">
	<td>
		<?php echo $route->package; ?>
	</td>
	<td>
		<code><?php echo $route->name; ?></code>
	</td>
	<td>
		<code><?php echo $route->pattern; ?></code>
	</td>
	<td>
		<?php echo $route->methods ? strtoupper(implode(', ', $route->methods)) : 'All'; ?>
	</td>
	<td>
		<code><?php echo $route->extra['controller']; ?></code>
	</td>
</tr>

<?php endforeach; ?>
</tbody>
</table>

<?php $this->endblock() ?>
