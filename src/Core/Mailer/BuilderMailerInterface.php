<?php

declare(strict_types=1);

namespace Windwalker\Core\Mailer;

interface BuilderMailerInterface
{
    public function buildBody(\Closure $handler, string|false|null $layout = null): string;
}
