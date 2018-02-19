<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Profiler\Point\Collector;

$this->extend('_global.html');

/**
 * @var  Collector $collector
 */
?>

<?php $this->block('page_title') ?>Events<?php $this->endblock(); ?>

<?php $this->block('content') ?>

    <h2>Event Triggered</h2>

<?php echo $this->load('events', ['events' => $executed]); ?>

    <br/><br/>

    <h2>Event Not Triggered (But listeners registered)</h2>

<?php echo $this->load('events', ['events' => $noExecuted]); ?>

<?php $this->endblock() ?>
