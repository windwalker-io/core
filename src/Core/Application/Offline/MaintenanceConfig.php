<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\Offline;

use Windwalker\Data\ValueObject;

/**
 * The OfflineConfig class.
 */
class MaintenanceConfig extends ValueObject
{
    public array $allowedIps = [];

    public string $redirect = '';

    public string $secret = '';

    public string $template = '';

    public function addAllowedIps(...$ips): static
    {
        $this->allowedIps = array_merge(
            $this->allowedIps,
            $ips
        );

        return $this;
    }

    public function getAllowedIps(): array
    {
        return $this->allowedIps;
    }

    public function setAllowedIps(array $allowedIps): static
    {
        $this->allowedIps = $allowedIps;

        return $this;
    }

    public function getRedirect(): string
    {
        return $this->redirect;
    }

    public function setRedirect(string|\Stringable $redirect): static
    {
        $this->redirect = (string) $redirect;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }
}
