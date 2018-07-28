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

<div class="card border-<?php echo $timeline['time']['style'] ?>">
    <div class="card-header">
        <div id="query-<?php echo $name; ?>" style="position: relative; top: -70px;"></div>
        <div class="d-flex">
            <h4 class="card-title d-lg-inline-block mb-0">
                Query: <?php echo $timeline['data']['serial'] ?>
            </h4>

            <div class="query-actions d-lg-inline-block ml-auto">
                <a class="btn btn-sm btn-outline-primary" href="#query-<?php echo $name; ?>">
                    <span class="fa far fa-link"></span>
                </a>

                <a class="btn btn-sm btn-outline-success hasTooltip"
                    href="<?php echo $router->route(
                        'database',
                        ['id' => $item->id, 'refresh' => 1, 'hash' => 'query-' . $name]
                    ); ?>"
                    title="Refresh and back to this query.">
                    <span class="fa far fa-sync"></span>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <pre class="bg-light p-4"><?php echo $view->highlightQuery($timeline['data']['query']); ?></pre>
        <hr/>
        <div class="d-flex">
            <div>
                Query Time: <span
                    class="badge badge-<?php echo $timeline['time']['style'] ?>">
                    <?php echo round($timeline['time']['value'], 2) ?> ms</span>
                Memory: <span
                    class="badge badge-<?php echo $timeline['memory']['style'] ?>">
                    <?php echo round($timeline['memory']['value'], 3) ?> MB</span>
                Return Rows: <span class="badge badge-info"><?php echo $timeline['data']['rows'] ?></span>
            </div>
            <div class="ml-auto">
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                    data-target="#backtrace-modal-<?php echo $timeline['data']['serial']; ?>">
                    <span class="fa fa-list"></span>
                    Backtrace
                </button>
            </div>
        </div>

    </div>
    <?php if (!empty($timeline['data']['bounded'])) : ?>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th class="text-nowrap">Bounded Key</th>
                    <th class="text-nowrap">Value</th>
                    <th class="text-nowrap">Data Type</th>
                    <th class="text-nowrap">Length</th>
                    <th class="text-nowrap">Driver Options</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ((array) $timeline['data']['bounded'] as $key => $item) : ?>
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
        </div>
    <?php endif; ?>

    <?php if (isset($timeline['data']['explain'])) : ?>
    <div class="table-responsive">
        <table class="explain-table table table-striped mb-0">
            <thead>
            <tr>
                <th class="text-nowrap">ID</th>
                <th class="text-nowrap">Select Type</th>
                <th class="text-nowrap">Table</th>
                <th class="text-nowrap">Type</th>
                <th class="text-nowrap">Possible Keys</th>
                <th class="text-nowrap">Key</th>
                <th class="text-nowrap">Key Length</th>
                <th>Reference</th>
                <th class="text-nowrap">Rows</th>
                <th width="10%">Extra</th>
            </tr>
            </thead>
            <tbody>
            <?php $explain = new DataSet($timeline['data']['explain']); ?>

            <?php foreach ($explain as $item) : ?>
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
    </div>
    <?php endif; ?>

    <?php if (isset($timeline['data']['backtrace'])) : ?>
        <div class="modal fade backtrace-modal" id="backtrace-modal-<?php echo $timeline['data']['serial']; ?>" tabindex="-1"
            role="dialog" aria-labelledby="backtrace-modal-label-<?php echo $timeline['data']['serial']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="backtrace-modal-label-<?php echo $timeline['data']['serial']; ?>">
                            Query <?php echo $timeline['data']['serial']; ?> Backtrace
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <?php $num = count($timeline['data']['backtrace']); ?>

                            <?php foreach ($timeline['data']['backtrace'] as $trace) : ?>
                            <tr>
                                <td class="text-nowrap">
                                    <?php echo $num--; ?>
                                </td>
                                <td class="48%">
                                    <?php echo $trace['function']; ?>
                                </td>
                                <td class="48%">
                                    <?php echo $trace['file']; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Backtrace -->
</div>
