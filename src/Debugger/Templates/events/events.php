<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

?>
<table class="table table-bordered">
    <thead>
    <tr>
        <th>Event Name</th>
        <th>Times</th>
        <th>Listener</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($events as $name => $listeners): ?>
        <?php if (count($listeners)): ?>
            <?php $i = 0; ?>
            <?php foreach ($listeners as $event): ?>
                <tr>
                    <?php if ($i == 0): ?>
                        <td rowspan="<?php echo count($listeners); ?>">
                            <?php echo $name; ?>
                        </td>
                    <?php endif; ?>
                    <td>
                        <?php echo $event['times']; ?>
                    </td>
                    <td>
                        <code><?php echo $event['listener']; ?></code>
                    </td>
                </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td>
                    <?php echo $name; ?>
                </td>
                <td>
                    -
                </td>
                <td>
                    -
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
