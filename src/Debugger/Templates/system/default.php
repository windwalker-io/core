<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Debugger\Html\BootstrapKeyValueGrid;
use Windwalker\Profiler\Point\Collector;

$this->extend('_global.html');

/**
 * @var  Collector $collector
 */
?>

<?php $this->block('page_title') ?>System<?php $this->endblock(); ?>

<?php $this->block('content') ?>
<h2>Windwalker</h2>

<table class="table table-bordered">
    <tbody>
    <tr>
        <td width="30%">
            Framework Version
        </td>
        <td>
            <?php echo $collector['windwalker.version.framework']; ?>
        </td>
    </tr>
    <tr>
        <td>
            Core Version
        </td>
        <td>
            <?php echo $collector['windwalker.version.core']; ?>
        </td>
    </tr>
    <tr>
        <td width="30%">
            PHP
        </td>
        <td>
            <?php echo $collector['system.php.version']; ?>
        </td>
    </tr>
    </tbody>
</table>

<br/><br/>

<h2>Custom Data</h2>

<?php
echo BootstrapKeyValueGrid::create()
    ->addHeader()
    ->addItems((array) $collector['custom.data']);
?>

<div class="alert alert-info">
    <p>
        Add Custom data by use <code>Windwalker\Debugger\Helper\DebuggerHelper::addCustomData('key', $value)</code>
    </p>
</div>

<br/><br/>

<h2>Debug Messages</h2>

<?php
echo BootstrapKeyValueGrid::create()
    ->addHeader('Type', 'Message')
    ->configure((array) $collector['debug.messages'], function (BootstrapKeyValueGrid $grid, $key, $item) {
        $grid->addItem(
            @$item['type'],
            @$item['message'],
            [BootstrapKeyValueGrid::ROW => ['class' => @$item['type']]]
        );
    });
?>

<br/><br/>

<h2>Config</h2>

<pre class="bg-light p-4"><?php echo \Symfony\Component\Yaml\Yaml::dump($collector['windwalker.config'], 5); ?></pre>

<?php $this->endblock() ?>
