<?php
/**
 * Part of formosa project.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

/** @var $exception Exception */
$exception = $data->exception;
?>
Message: <?php echo $exception->getMessage(); ?>

Code: <?php echo $exception->getCode(); ?>

File: <?php echo $exception->getFile(); ?>

Line: <?php echo $exception->getLine(); ?>
