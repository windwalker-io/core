<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2018 LYRASOFT.
 * @license    LGPL-2.0-or-later
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
