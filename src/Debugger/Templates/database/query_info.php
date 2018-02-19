<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Data\DataSet;

$types = [
    PDO::PARAM_BOOL => 'BOOL',
    PDO::PARAM_NULL => 'NULL',
    PDO::PARAM_INT => 'INT',
    PDO::PARAM_STR => 'STR',
    PDO::PARAM_LOB => 'LOB',
    PDO::PARAM_STMT => 'STMT',
    PDO::PARAM_INPUT_OUTPUT => 'INPUT_OUTPUT',
];
?>

<div class="panel panel-<?php echo $timeline['time']['style'] ?>">
    <div class="panel-heading">
        <div id="query-<?php echo $name; ?>" style="position: relative; top: -70px;"></div>
        <div class="pull-right">
            <a href="#query-<?php echo $name; ?>">
                <span class="glyphicon glyphicon-link"></span>
            </a>
            &nbsp;
            &nbsp;
            <a href="<?php echo $router->route('database',
                ['id' => $item->id, 'refresh' => 1, 'hash' => 'query-' . $name]); ?>"
               class="hasTooltip" title="Refresh and back to this query.">
                <span class="glyphicon glyphicon-refresh"></span>
            </a>
        </div>
        <h3 class="panel-title">
            Query: <?php echo $timeline['data']['serial'] ?>
        </h3>
    </div>
    <div class="panel-body">
        <pre><?php echo $view->highlightQuery($timeline['data']['query']); ?></pre>
        <hr/>
        Query Time: <span
            class="label label-<?php echo $timeline['time']['style'] ?>"><?php echo round($timeline['time']['value'],
                2) ?> ms</span>
        Memory: <span
            class="label label-<?php echo $timeline['memory']['style'] ?>"><?php echo round($timeline['memory']['value'],
                3) ?> MB</span>
        Return Rows: <span class="label label-info"><?php echo $timeline['data']['rows'] ?></span>
    </div>
    <?php if (!empty($timeline['data']['bounded'])): ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Bounded Key</th>
                <th>Value</th>
                <th>Data Type</th>
                <th>Length</th>
                <th>Driver Options</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ((array) $timeline['data']['bounded'] as $key => $item): ?>
                <tr>
                    <td><?php echo $key ?></td>
                    <td><?php echo $item['value'] ?></td>
                    <td><?php echo isset($types[$item['dataType']]) ? $types[$item['dataType']] : 'UNKNOWN' ?></td>
                    <td><?php echo $item['length'] ?></td>
                    <td><?php echo print_r($item['driverOptions'], true) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <hr/>
    <?php endif; ?>

    <?php if (isset($timeline['data']['explain'])): ?>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Select Type</th>
                <th>Table</th>
                <th>Type</th>
                <th>Possible Keys</th>
                <th>Key</th>
                <th>Key Length</th>
                <th>Reference</th>
                <th>Rows</th>
                <th width="10%">Extra</th>
            </tr>
            </thead>
            <tbody>
            <?php $explain = new DataSet($timeline['data']['explain']); ?>
            <?php foreach ($explain as $item): ?>
                <tr>
                    <td><?php echo $item->id ?></td>
                    <td><?php echo $item->select_type ?></td>
                    <td><?php echo $item->table ?></td>
                    <td><?php echo $item->type ?></td>
                    <td><?php echo str_replace(',', ', ', $item->possible_keys) ?></td>
                    <td><strong><?php echo $item->key ?></strong></td>
                    <td><?php echo $item->key_len ?></td>
                    <td><?php echo $item->ref ?></td>
                    <td><?php echo $item->rows ?></td>
                    <td><?php echo $item->Extra ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>
