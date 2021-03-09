<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Console;

use Windwalker\Console\Command\Command;
use Windwalker\Console\Console;
use Windwalker\DI\Annotation\AbstractAnnotation;

/**
 * The CommandMeta class.
 *
 * @Annotation
 *
 * @since  __DEPLOY_VERSION__
 */
class CommandMeta extends AbstractAnnotation
{
    public function __invoke(Console $console, Command $command)
    {
        $command->description($this->getOption('description'));

        $options = (array) $this->getOption('options');

        /** @var CommandOption $option */
        foreach ($options as $option) {
            $command->addOption($option->getOption('name'))
                ->alias($option->getOption('alias'))
                ->description($option->getOption('description'))
                ->defaultValue($option->getOption('default'));
        }
    }
}
