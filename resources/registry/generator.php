<?php

declare(strict_types=1);

return [
    'controller' => \Windwalker\Core\Generator\SubCommand\ControllerSubCommand::class,
    'view' => \Windwalker\Core\Generator\SubCommand\ViewSubCommand::class,
    'model' => \Windwalker\Core\Generator\SubCommand\ModelSubCommand::class,
    'entity' => \Windwalker\Core\Generator\SubCommand\EntitySubCommand::class,
    'data' => \Windwalker\Core\Generator\SubCommand\DataSubCommand::class,
    'form' => \Windwalker\Core\Generator\SubCommand\FormSubCommand::class,
    'route' => \Windwalker\Core\Generator\SubCommand\RouteSubCommand::class,
    'enum' => \Windwalker\Core\Generator\SubCommand\EnumSubCommand::class,
    'component' => \Windwalker\Core\Generator\SubCommand\ComponentSubCommand::class,
    'field' => \Windwalker\Core\Generator\SubCommand\FieldSubCommand::class,
    'policy' => \Windwalker\Core\Generator\SubCommand\PolicySubCommand::class,
    'middleware' => \Windwalker\Core\Generator\SubCommand\MiddlewareSubCommand::class,
    'job' => \Windwalker\Core\Generator\SubCommand\QueueJobSubCommand::class,
    'subscriber' => \Windwalker\Core\Generator\SubCommand\SubscriberSubCommand::class,
    'command' => \Windwalker\Core\Generator\SubCommand\CommandSubCommand::class,
];
