<?php

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

    /**
     * @codeCoverageIgnore
     */
    public function getAllowedIps(): array
    {
        return $this->allowedIps;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setAllowedIps(array $allowedIps): static
    {
        $this->allowedIps = $allowedIps;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getRedirect(): string
    {
        return $this->redirect;
    }

    public function setRedirect(string|\Stringable $redirect): static
    {
        $this->redirect = (string) $redirect;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setSecret(string $secret): static
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setTemplate(string $template): static
    {
        $this->template = $template;

        return $this;
    }
}
