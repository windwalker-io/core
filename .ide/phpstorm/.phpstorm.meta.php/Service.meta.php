<?php

namespace PHPSTORM_META {

    // Container
    override(
        \Windwalker\Core\Application\ApplicationInterface::make(0),
        type(0)
    );
    override(
        \Windwalker\Core\Application\ApplicationInterface::service(0),
        type(0)
    );
    override(
        \Windwalker\Core\Application\ApplicationInterface::resolve(0),
        type(0)
    );

    // Field
    override(
        \Windwalker\Core\Form\AbstractFieldDefinition::addField(0),
        map(
            [
                '' => '@',
            ]
        )
    );

    override(
        \Windwalker\Form\Form::addField(0),
        map(
            [
                '' => '@',
            ]
        )
    );

    override(
        \Windwalker\Form\Form::add(0),
        map(
            [
                '' => '@',
            ]
        )
    );

    // Data
    override(
        \Windwalker\Data\Traits\CollectionTrait::as(0),
        map(
            [
                '' => '@',
            ]
        )
    );

    // Helpers
    override(
        \Windwalker\tap(0),
        elementType(0)
    );
}
