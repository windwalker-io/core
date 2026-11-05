<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Windwalker\DI\Parameters;
use Windwalker\Crypt\SecretToolkit;

/**
 * Provides the app secret, or a sub-key derived from it for a specific purpose.
 */
class SecretKeeper implements SecretKeeperInterface
{
    public function __construct(protected Parameters $config) {}

    public function getSecret(?string $deriveFor = null): string
    {
        $secret = (string) SecretToolkit::decodeIfHasPrefix((string) $this->config->getDeep('app.secret'));

        if ($deriveFor) {
            $secret = hash_hkdf('sha256', $secret, 32, $deriveFor);
        }

        return $secret;
    }
}
