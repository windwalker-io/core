<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace PHPSTORM_META {
    // Container
    override(
        \Windwalker\Core\Application\ServiceAwareTrait::make(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\Core\Application\ServiceAwareTrait::service(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\DI\Container::get(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\DI\Container::newInstance(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\DI\Container::createSharedObject(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\DI\Container::createObject(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\Core\Ioc::make(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\Core\Ioc::get(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\Core\Ioc::service(0),
        map([
            '' => '@'
        ])
    );

    // Field
    override(
        \Windwalker\Core\Form\AbstractFieldDefinition::addField(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\Form\Form::addField(0),
        map([
            '' => '@'
        ])
    );

    override(
        \Windwalker\Form\Form::add(0),
        map([
            '' => '@'
        ])
    );

    // Data
    override(
        \Windwalker\Data\Traits\CollectionTrait::as(0),
        map([
            '' => '@'
        ])
    );

    // Helpers
    override(
        \Windwalker\tap(0),
        elementType(0)
    );
}
