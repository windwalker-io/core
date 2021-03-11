<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace PHPSTORM_META {

    // Container
    registerArgumentsSet(
        'argument_options',
        \Symfony\Component\Console\Input\InputArgument::REQUIRED,
        \Symfony\Component\Console\Input\InputArgument::OPTIONAL,
        \Symfony\Component\Console\Input\InputArgument::IS_ARRAY,
    );

    expectedArguments(
        \Symfony\Component\Console\Command\Command::addArgument(),
        1,
        argumentsSet('argument_options')
    );
    registerArgumentsSet(
        'option_options',
        \Symfony\Component\Console\Input\InputOption::VALUE_NONE,
        \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
        \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
        \Symfony\Component\Console\Input\InputOption::VALUE_IS_ARRAY,
        \Symfony\Component\Console\Input\InputOption::VALUE_NEGATABLE,
    );

    expectedArguments(
        \Symfony\Component\Console\Command\Command::addOption(),
        2,
        argumentsSet('option_options')
    );
}
