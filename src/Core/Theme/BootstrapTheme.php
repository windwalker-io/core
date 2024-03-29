<?php

declare(strict_types=1);

namespace Windwalker\Core\Theme;

/**
 * The BootstrapTheme class.
 */
class BootstrapTheme extends AbstractTheme
{
    public function getViewPrefix(): string
    {
        return 'ui/bootstrap';
    }
}
