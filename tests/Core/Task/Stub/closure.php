<?php

/**
 * Source-backed Closure for subprocess output and host CLI smoke tests.
 * Remove when those serialized Closure tests are removed.
 */

declare(strict_types=1);

return static function (): array {
    echo "progress\n";

    return ['ok' => true];
};
