<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace PHPSTORM_META {

    use Windwalker\Core\Form\AbstractFieldDefinition;
    use Windwalker\Core\Ioc;
    use Windwalker\Core\WindwalkerTrait;
    use Windwalker\Core\Application\ServiceAwareTrait;
    use Windwalker\Data\Traits\CollectionTrait;
    use Windwalker\DI\Container;
    use Windwalker\Form\Form;

    // Container
    override(
        ServiceAwareTrait::make(0),
        map([
            '' => '@'
        ])
    );

    override(
        ServiceAwareTrait::service(0),
        map([
            '' => '@'
        ])
    );

    override(
        Container::newInstance(0),
        map([
            '' => '@'
        ])
    );

    override(
        Container::createSharedObject(0),
        map([
            '' => '@'
        ])
    );

    override(
        Container::createObject(0),
        map([
            '' => '@'
        ])
    );

    override(
        Ioc::make(0),
        map([
            '' => '@'
        ])
    );

    override(
        Ioc::get(0),
        map([
            '' => '@'
        ])
    );

    override(
        Ioc::service(0),
        map([
            '' => '@'
        ])
    );

    // Field
    override(
        AbstractFieldDefinition::addField(0),
        map([
            '' => '@'
        ])
    );

    override(
        Form::addField(0),
        map([
            '' => '@'
        ])
    );

    override(
        Form::add(0),
        map([
            '' => '@'
        ])
    );

    // Data
    override(
        CollectionTrait::as(0),
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
