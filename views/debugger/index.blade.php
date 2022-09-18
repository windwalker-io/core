<?php

/**
 * Global variables
 * --------------------------------------------------------------
 * @var $app       AppContext      Application context.
 * @var $vm        object          The view model object.
 * @var $uri       SystemUri       System Uri information.
 * @var $chronos   ChronosService  The chronos datetime service.
 * @var $nav       Navigator       Navigator object to build route.
 * @var $asset     AssetService    The Asset manage service.
 * @var $lang      LangService     The language translation service.
 */

declare(strict_types=1);

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

$data = [
    'systemPath' => WINDWALKER_ROOT,
    'editor' => $app->config('debugger.editor') ?: 'phpstorm'
];

?><!doctype html>
<html lang="en">
<head>
    <base href="{{ $uri->root() }}" />
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Windwalker Debugger</title>
    <script>
        document.__data = {!! json_encode($data) !!};
    </script>
</head>
<body>
    <app id="app"></app>

    <script>
        window.externalPublicPath = '{{ $asset->handleUri('@core/debugger/') }}'
    </script>
    <script src="{{ $asset->appendVersion($asset->handleUri('@core/debugger/debugger.js')) }}"></script>
</body>
</html>
