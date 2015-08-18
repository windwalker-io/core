<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

use Windwalker\Profiler\Profiler;

$this->extend('_global.html');

/**
 * @var  Profiler  $profiler
 */
?>

<?php $this->block('page_title') ?>Timeline<?php $this->endblock(); ?>

<?php $this->block('content') ?>
<h2>System Process</h2>

<?php echo $this->load('timeline', array('timeline' => $systemProcess)) ?>

	<br /><br />

<h2>All Process</h2>

<?php echo $this->load('timeline', array('timeline' => $allProcess)) ?>
<?php $this->endblock() ?>