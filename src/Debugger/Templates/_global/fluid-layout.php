<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

$this->extend('_global.html');
?>

<?php $this->block('body') ?>
<div class="main-body container-fluid">
    <?php $this->block('content') ?>
    Content
    <?php $this->endblock(); ?>
</div>
<?php $this->endblock(); ?>
