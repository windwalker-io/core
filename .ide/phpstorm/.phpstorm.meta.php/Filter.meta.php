<?php

namespace PHPSTORM_META {

    registerArgumentsSet(
        'filters',
        'abs',
        'alnum',
        'cmd',
        'email',
        'url',
        'words',
        'ip',
        'ipv4',
        'ipv6',
        'neg',
        'raw',
        'range(min=int, max=int)',
        'range',
        'clamp(min=int, max=int)',
        'clamp',
        'length(max=int, [utf8])',
        'length',
        'regex(regex=string, type=\'match\'|\'replace\')',
        'regex',
        'required',
        'default(value=mixed)',
        'func',
        'string',
        'int',
        'float',
        'array',
        'bool',
        'object'
    );

    expectedArguments(
        \Windwalker\Core\Attributes\Filter::__construct(),
        0,
        argumentsSet('filters')
    );

    expectedArguments(
        \Windwalker\Core\Attributes\Assert::__construct(),
        0,
        argumentsSet('filters')
    );
}
