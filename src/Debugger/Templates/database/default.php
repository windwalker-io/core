<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Debugger\Helper\TimelineHelper;
use Windwalker\Debugger\Html\BootstrapKeyValueGrid;
use Windwalker\Dom\HtmlElement;
use Windwalker\Profiler\Profiler;

$this->extend('_global.html');

/**
 * @var  Profiler $profiler
 */
?>

<?php $this->block('page_title') ?>Database<?php $this->endblock(); ?>

<?php $this->block('content') ?>

<style>
    @media (min-width: 992px) {
        .backtrace-modal .modal-dialog {
            max-width: 1200px;
        }
    }

    .backtrace-modal .modal-dialog table td {
        font-size: 13px;
        word-break: break-all;
        font-family: monospace;
    }

    .explain-table tbody td {
        font-size: 13px;
        font-family: monospace;
    }
</style>

    <h2>Database Information</h2>

<?php
echo BootstrapKeyValueGrid::create()
    ->addHeader('Key', 'Value')
    ->addItem('Database Driver', $collector['database.driver.name'])
    ->addItem('Database Driver Class', \Windwalker\h('code', [], $collector['database.driver.class']))
    ->addTitle(\Windwalker\h('strong', [], 'Options'))
    ->addItems($options);
?>

    <br/><br/>

    <div id="queries" style="position: relative; top: -50px;"></div>
    <h2>Queries</h2>

    <p>
       <span>Queries: <span class="badge badge-info"><?php echo $queryTimes; ?></span>
       <span></span>
           Query Time: <span class="badge badge-<?php echo TimelineHelper::getStateColor($queryTotalTime, 15 * $queryTimes) ?>">
               <?php echo round($queryTotalTime, 2); ?>ms
           </span>
       </span>
       <span>
           Memory: <span class="badge badge-<?php echo TimelineHelper::getStateColor($queryTotalMemory, 0.01 * $queryTimes) ?>">
               <?php echo round($queryTotalMemory, 3); ?>MB
           </span>
       </span>
    </p>

<?php foreach ((array) $queryProcess as $name => $timeline) : ?>
    <br/>
    <?php echo $this->load('query_info', ['name' => $name, 'timeline' => $timeline]) ?>
    <br/>
<?php endforeach; ?>

    <script src="<?php echo $router->route('asset', ['type' => 'bootstrap-js']); ?>" async></script>

<?php $this->endblock() ?>
