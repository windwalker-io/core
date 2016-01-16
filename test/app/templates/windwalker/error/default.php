<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

/** @var $exception Exception */
$exception = $data->exception;
?>
Message: <?php echo $exception->getMessage(); ?>

Code: <?php echo $exception->getCode(); ?>

File: <?php echo $exception->getFile(); ?>

Line: <?php echo $exception->getLine(); ?>
