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

/**
 * @var $collector \Windwalker\Data\Collection
 * @var $profiler \Windwalker\Core\Profiler\Profiler
 */

$profiler = $collector->getDeep('profiler.main');

?>
<link rel="stylesheet" href="{{ $css }}" nonce="{{ $nonce }}" />
<div class="ww-debug-console wd-text-white wd-flex wd-bg-gray-800 wd-w-full wd-fixed wd-bottom-0"
    style="height: 50px; z-index: 2050; overflow-x: scroll; overflow-y: hidden;">
    <div class="wd-p-3">
        <a href="https://windwalker.io" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 273.84 44.18" height="25" width="300" xmlns:v="https://vecta.io/nano"><defs><linearGradient id="A" x1="30.36" y1="25.23" x2="36.5" y2="20.93" gradientUnits="userSpaceOnUse"><stop offset="0"/><stop offset=".07" stop-opacity=".83"/><stop offset=".5" stop-opacity=".65"/><stop offset=".93" stop-opacity=".83"/><stop offset="1"/></linearGradient><linearGradient id="B" x1="61.28" y1="38.13" x2="11.96" y2="-11.19" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#38f8d6"/><stop offset=".61" stop-color="#43e87b"/></linearGradient><path id="C" d="M53.23,0H43.8a.47.47,0,0,0-.39.74L61.79,26.56a.47.47,0,0,0,.85-.28V9.41A9.41,9.41,0,0,0,53.23,0Z"/><path id="D" d="M7.89.5H.47a.47.47,0,0,0-.38.74L24,34.67a9.38,9.38,0,0,0,7.65,3.93H40L15.54,4.43A9.38,9.38,0,0,0,7.89.5Z"/><path id="E" d="M42.83,11.48a9.44,9.44,0,0,0-7.65-3.93H26.84L46.26,34.67a9.4,9.4,0,0,0,7.65,3.93h6.5a.94.94,0,0,0,.77-1.49Z"/></defs><path d="M26.84 20.2l13.17 18.4V25.95L26.84 7.55V20.2z" fill="#00aa61"/><g opacity=".5"><path d="M26.84 20.2l13.17 18.4V25.95L26.84 7.55V20.2z" fill="url(#A)"/></g><g fill="#fff"><use xlink:href="#C"/><use xlink:href="#D"/><use xlink:href="#E"/></g><g fill="url(#B)"><use xlink:href="#C"/><use xlink:href="#D"/><use xlink:href="#E"/></g><path d="M106.48 11a3 3 0 0 1 1.68-.51 2.87 2.87 0 0 1 2 .72 2.39 2.39 0 0 1 .84 1.93 4.44 4.44 0 0 1-.27 1.36L103.84 33a2.77 2.77 0 0 1-1.24 1.48 3.87 3.87 0 0 1-2 .53 3.8 3.8 0 0 1-2-.53A2.79 2.79 0 0 1 97.41 33L93 20.38 88.37 33a2.94 2.94 0 0 1-1.26 1.48 3.71 3.71 0 0 1-1.93.53 3.81 3.81 0 0 1-2-.53A2.78 2.78 0 0 1 82 33l-6.87-18.5a3.85 3.85 0 0 1-.24-1.29 2.43 2.43 0 0 1 .9-2 3.26 3.26 0 0 1 2.09-.73 3.3 3.3 0 0 1 1.76.5 2.71 2.71 0 0 1 1.14 1.47l4.65 13.4 4.93-13.4A2.89 2.89 0 0 1 91.45 11a3 3 0 0 1 1.68-.51 2.84 2.84 0 0 1 2.76 2l4.62 13.67 4.86-13.71a2.89 2.89 0 0 1 1.11-1.45zm7.85 3.44a2.69 2.69 0 0 1-.9-2.12 2.64 2.64 0 0 1 .9-2.11 4.14 4.14 0 0 1 4.84 0 2.6 2.6 0 0 1 .92 2.11 2.69 2.69 0 0 1-.9 2.12 4.09 4.09 0 0 1-4.86 0zm.27 19.76a2.52 2.52 0 0 1-.87-2.09v-11.7a2.51 2.51 0 0 1 .87-2.09 3.68 3.68 0 0 1 4.32 0 2.51 2.51 0 0 1 .87 2.09v11.7a2.52 2.52 0 0 1-.87 2.09 3.73 3.73 0 0 1-4.32 0zM139 19.34a8.47 8.47 0 0 1 1.46 5.42v7.35a2.62 2.62 0 0 1-.82 2 3.67 3.67 0 0 1-4.42 0 2.64 2.64 0 0 1-.81-2V25a3.57 3.57 0 0 0-.53-2.17 1.86 1.86 0 0 0-1.58-.68 2.78 2.78 0 0 0-2.16.87 3.29 3.29 0 0 0-.8 2.32v6.74a2.62 2.62 0 0 1-.82 2 3.67 3.67 0 0 1-4.42 0 2.64 2.64 0 0 1-.81-2V20.34a2.5 2.5 0 0 1 .85-1.95 3.16 3.16 0 0 1 2.21-.77 2.86 2.86 0 0 1 2.05.72 2.43 2.43 0 0 1 .77 1.87 5.89 5.89 0 0 1 2.28-2 7 7 0 0 1 3.09-.68 5.44 5.44 0 0 1 4.46 1.81zm21.71-8.02a2.49 2.49 0 0 1 .86 1.95v18.84a2.64 2.64 0 0 1-.81 2 3.1 3.1 0 0 1-2.18.74 3.14 3.14 0 0 1-2.06-.66 2.56 2.56 0 0 1-.9-1.82 4.78 4.78 0 0 1-2.09 1.87 6.69 6.69 0 0 1-3 .68 6.89 6.89 0 0 1-3.83-1.1 7.46 7.46 0 0 1-2.7-3.07 10.52 10.52 0 0 1-.95-4.59 10.43 10.43 0 0 1 .93-4.54 7 7 0 0 1 2.62-3 7.14 7.14 0 0 1 3.86-1.06 6.73 6.73 0 0 1 2.94.65 5.28 5.28 0 0 1 2.09 1.73v-6.77a2.45 2.45 0 0 1 .8-1.91 3.14 3.14 0 0 1 2.16-.71 3.27 3.27 0 0 1 2.26.77zm-5.94 18a5 5 0 0 0 .85-3.13 4.82 4.82 0 0 0-.85-3.07 3.32 3.32 0 0 0-4.86 0 4.69 4.69 0 0 0-.85 3 5.05 5.05 0 0 0 .87 3.16 2.84 2.84 0 0 0 2.39 1.12 2.9 2.9 0 0 0 2.45-1.05zm33.65-11.27a2.32 2.32 0 0 1 1.37-.43 3 3 0 0 1 2 .77 2.36 2.36 0 0 1 .88 1.85 2.81 2.81 0 0 1-.27 1.12l-5.51 11.8a2.81 2.81 0 0 1-1.14 1.31 3.17 3.17 0 0 1-1.68.46 3.11 3.11 0 0 1-1.67-.46 2.88 2.88 0 0 1-1.12-1.31L178.06 26l-3 7.17a2.65 2.65 0 0 1-1.11 1.31 3.17 3.17 0 0 1-1.68.46 3.23 3.23 0 0 1-1.69-.46 3 3 0 0 1-1.17-1.31l-5.47-11.8a2.52 2.52 0 0 1-.24-1.09 2.33 2.33 0 0 1 .94-1.88 3.2 3.2 0 0 1 2.09-.77 2.61 2.61 0 0 1 1.47.43 2.57 2.57 0 0 1 1 1.31l3.23 7.85 3.3-7.82a2.51 2.51 0 0 1 1.05-1.29 2.88 2.88 0 0 1 3.06 0 2.51 2.51 0 0 1 1 1.29l3.4 7.85 3.17-7.92a2.64 2.64 0 0 1 1.01-1.28zm19.72 1.36c1.23 1.23 1.86 3.11 1.86 5.59v7.11a2.65 2.65 0 0 1-.77 2 2.94 2.94 0 0 1-2.12.73 2.8 2.8 0 0 1-2-.68 2.5 2.5 0 0 1-.83-1.83 3.77 3.77 0 0 1-1.6 1.9A5 5 0 0 1 200 35a7 7 0 0 1-3.13-.69 5.61 5.61 0 0 1-2.2-1.94 5.09 5.09 0 0 1-.79-2.81 4.22 4.22 0 0 1 .93-2.87 5.54 5.54 0 0 1 3-1.55 25.09 25.09 0 0 1 5.5-.47h.92v-.41a2.32 2.32 0 0 0-.54-1.72 2.61 2.61 0 0 0-1.84-.53 13.41 13.41 0 0 0-4.08 1 4.17 4.17 0 0 1-1.32.27 1.7 1.7 0 0 1-1.36-.61 2.42 2.42 0 0 1-.51-1.6 2.29 2.29 0 0 1 .3-1.23 2.74 2.74 0 0 1 1-.85 12.18 12.18 0 0 1 3-1 17.34 17.34 0 0 1 3.54-.38q3.86-.06 5.72 1.8zm-4.68 10.64a3.21 3.21 0 0 0 .78-2.23v-.41h-.54a8.47 8.47 0 0 0-3.1.4 1.33 1.33 0 0 0-.91 1.3 1.78 1.78 0 0 0 .49 1.3 1.69 1.69 0 0 0 1.27.51 2.58 2.58 0 0 0 2.01-.87zm10.79 4.15a2.55 2.55 0 0 1-.86-2.09V13.37a2.6 2.6 0 0 1 .86-2.11 3.64 3.64 0 0 1 4.32 0 2.58 2.58 0 0 1 .87 2.11v18.74a2.52 2.52 0 0 1-.87 2.09 3.73 3.73 0 0 1-4.32 0zm26.27-2.2a2.93 2.93 0 0 1-.8 2 2.48 2.48 0 0 1-1.92.88 2.88 2.88 0 0 1-1.91-.85L229 27.45V32a2.6 2.6 0 0 1-.86 2.11 3.64 3.64 0 0 1-4.32 0 2.58 2.58 0 0 1-.87-2.11V13.37a2.58 2.58 0 0 1 .87-2.11 3.64 3.64 0 0 1 4.32 0 2.6 2.6 0 0 1 .86 2.11V24.9l6.29-6.39a2.64 2.64 0 0 1 3.9-.06 2.64 2.64 0 0 1 .83 1.89 2.74 2.74 0 0 1-.88 1.94L235.42 26l4.22 4a2.69 2.69 0 0 1 .88 2zm16.88-2.07a2.46 2.46 0 0 1 .49 1.6 2.23 2.23 0 0 1-1.32 2.07 13 13 0 0 1-2.74 1 12.17 12.17 0 0 1-2.91.39 10.61 10.61 0 0 1-4.86-1.05 7.41 7.41 0 0 1-3.19-3 9.14 9.14 0 0 1-1.13-4.62 9.42 9.42 0 0 1 1.07-4.51 7.82 7.82 0 0 1 3-3.09 8.48 8.48 0 0 1 4.33-1.11 8.1 8.1 0 0 1 4.12 1 7 7 0 0 1 2.74 2.85 9.38 9.38 0 0 1 1 4.39 1.84 1.84 0 0 1-.36 1.21 1.26 1.26 0 0 1-1 .42h-9a3.77 3.77 0 0 0 1 2.35 3.62 3.62 0 0 0 2.42.71 6.3 6.3 0 0 0 1.56-.18 15.65 15.65 0 0 0 1.6-.53l1-.36a3.14 3.14 0 0 1 .91-.15 1.55 1.55 0 0 1 1.27.61zm-9-7.53a3.64 3.64 0 0 0-.85 2.23h5.33q-.19-3-2.58-3a2.47 2.47 0 0 0-1.89.77zm24.76-4.25a2.42 2.42 0 0 1 .68 1.85 2.63 2.63 0 0 1-.63 2 3.85 3.85 0 0 1-2.26.79l-1 .1a3.57 3.57 0 0 0-2.43 1.07 3.53 3.53 0 0 0-.73 2.36v5.82a2.52 2.52 0 0 1-.87 2.09 3.73 3.73 0 0 1-4.32 0 2.52 2.52 0 0 1-.87-2.09v-11.8a2.43 2.43 0 0 1 .87-2 3.18 3.18 0 0 1 2.09-.72 3 3 0 0 1 2 .68 2.43 2.43 0 0 1 .76 1.91v.64a4.52 4.52 0 0 1 1.79-2.31 5.09 5.09 0 0 1 2.6-.88h.47a2.28 2.28 0 0 1 1.85.49z" fill="#f2f2f2" /></svg>
        </a>
    </div>
    <a class="wd-px-5 wd-flex wd-items-center hover:wd-bg-gray-700 wd-no-underline"
        href="{{ $nav->to('debugger::home') }}#system/{{ $collector['id'] }}"
        target="_blank"
    >
        <div class="wd-bg-white wd-text-gray-800 wd-rounded-full wd-whitespace-nowrap wd-px-3"
        >
            <span class="wd-text-sm">
                V
                {{ $collector->getDeep('system.core_version') }}
            </span>
        </div>
    </a>
    <a class="wd-px-5 wd-flex wd-items-center hover:wd-bg-gray-700 wd-no-underline"
        href="{{ $nav->to('debugger::home') }}#request/{{ $collector['id'] }}"
        target="_blank"
    >
        <div class="wd-bg-white wd-text-gray-800 wd-rounded-full wd-whitespace-nowrap wd-px-3"
        >
            {{ $collector->getDeep('http.request.method') }}
        </div>
    </a>
    <a class="wd-px-5 wd-flex wd-items-center hover:wd-bg-gray-700 wd-no-underline"
        href="{{ $nav->to('debugger::home') }}#routing/{{ $collector['id'] }}"
        target="_blank"
    >
        <div class="wd-bg-white wd-text-gray-800 wd-rounded-full wd-whitespace-nowrap wd-px-3 " style="margin-right: 10px">
            {{ $collector->getDeep('http.response.status') }}
        </div>
        <div class="wd-text-white" style="font-family: monospace">
            {{ $collector->getDeep('routing.matched')->getName() }}
        </div>
    </a>
    <a class="wd-px-5 wd-flex wd-items-center hover:wd-bg-gray-700 wd-no-underline"
        href="{{ $nav->to('debugger::home') }}#timeline/{{ $collector['id'] }}"
        target="_blank"
    >
        <div class="wd-bg-white wd-text-gray-800 wd-rounded-full wd-whitespace-nowrap wd-px-3 ">
            <svg style="height: 14px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class=" wd-mb-1"><!--! Font Awesome Pro 6.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M232 120C232 106.7 242.7 96 256 96C269.3 96 280 106.7 280 120V243.2L365.3 300C376.3 307.4 379.3 322.3 371.1 333.3C364.6 344.3 349.7 347.3 338.7 339.1L242.7 275.1C236 271.5 232 264 232 255.1L232 120zM256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0zM48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48C141.1 48 48 141.1 48 256z"/></svg>
            {{ $profiler->getEndTime() }}ms
        </div>
    </a>
    <a class="wd-px-5 wd-flex wd-items-center hover:wd-bg-gray-700 wd-no-underline"
        href="{{ $nav->to('debugger::home') }}#timeline/{{ $collector['id'] }}"
        target="_blank"
    >
        <div class="wd-bg-white wd-text-gray-800 wd-rounded-full wd-whitespace-nowrap wd-px-3 ">
            <svg style="height: 14px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class=" wd-mb-1"><!--! Font Awesome Pro 6.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M320 0H141.3C124.3 0 108 6.7 96 18.7L18.7 96C6.7 108 0 124.3 0 141.3V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zM160 88v48c0 13.3-10.7 24-24 24s-24-10.7-24-24V88c0-13.3 10.7-24 24-24s24 10.7 24 24zm80 0v48c0 13.3-10.7 24-24 24s-24-10.7-24-24V88c0-13.3 10.7-24 24-24s24 10.7 24 24zm80 0v48c0 13.3-10.7 24-24 24s-24-10.7-24-24V88c0-13.3 10.7-24 24-24s24 10.7 24 24z"/></svg>
            {{ round($profiler->getMemoryPeak() / 1024 / 1024, 2) }}MB
        </div>
    </a>
    <a class="wd-px-5 wd-flex wd-items-center hover:wd-bg-gray-700 wd-no-underline"
        href="{{ $nav->to('debugger::home') }}#db/{{ $collector['id'] }}"
        target="_blank"
    >
        <div class="wd-bg-white wd-text-gray-800 wd-rounded-full wd-whitespace-nowrap wd-px-3"
        >
            <svg style="height: 14px" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="database" class="svg-inline--fa fa-database fa-w-14 wd-text-gray-800 wd-mb-1" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M448 73.12v45.75C448 159.1 347.6 192 224 192S0 159.1 0 118.9V73.12C0 32.88 100.4 0 224 0S448 32.88 448 73.12zM448 176v102.9C448 319.1 347.6 352 224 352S0 319.1 0 278.9V176c48.12 33.12 136.2 48.62 224 48.62S399.9 209.1 448 176zM448 336v102.9C448 479.1 347.6 512 224 512s-224-32.88-224-73.13V336c48.12 33.13 136.2 48.63 224 48.63S399.9 369.1 448 336z"></path></svg>
            <span class="wd-text-sm">
                <?php
                $count = 0;
                foreach ($collector->extract('db.queries') as $queries) {
                    $count += count($queries);
                }
                ?>
                {{ $count }}
            </span>
        </div>
    </a>
</div>
<script src="{{ $js }}" nonce="{{ $nonce }}"></script>
