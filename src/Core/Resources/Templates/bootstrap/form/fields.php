<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

use Windwalker\Form\Field\AbstractField;

/**
 * @var  AbstractField $field
 */
?>
<?php foreach ($fields as $field): ?>
    <div class="form-group">
        <?php
        $field->set('class', $field->get('class') . ' form-control');
        $field->set('labelClass', $field->get('labelClass') . ' control-label ' . $label_cols);
        ?>
        <?php echo $field->renderLabel(); ?>
        <div class="<?php echo $input_cols; ?>">
            <?php echo $field->renderInput(); ?>
        </div>
    </div>
<?php endforeach; ?>
