<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use JsonException;
use Windwalker\DOM\HTMLElement;

/**
 * The ImportMap class.
 */
class ImportMap
{
    protected array $data = [
        'imports' => [],
        'scopes' => [],
    ];

    protected string $cspNonce = '';

    /**
     * ImportMap constructor.
     *
     * @param  bool  $isDebug
     */
    public function __construct(protected bool $isDebug = false) {}

    /**
     * @codeCoverageIgnore
     */
    public function addImport(string $name, string $uri): static
    {
        $this->data['imports'][$name] = $uri;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function removeImport(string $name): static
    {
        unset($this->data['imports'][$name]);

        return $this;
    }

    public function getImport(string $name): ?string
    {
        return $this->data['imports'][$name] ?? null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getAllImports(): array
    {
        return $this->data['imports'];
    }

    /**
     * @codeCoverageIgnore
     */
    public function setImports(array $imports): static
    {
        $this->data['imports'] = $imports;

        return $this;
    }

    public function addImports(array $imports): static
    {
        $this->data['imports'] = array_merge(
            $this->data['imports'],
            $imports
        );

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function resetImports(): static
    {
        $this->data['imports'] = [];

        return $this;
    }

    public function addScope(string $scope, array $items): static
    {
        $this->data['scopes'][$scope] = array_merge(
            $this->data['scopes'][$scope],
            $items
        );

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setScope(string $scope, array $items): static
    {
        $this->data['scopes'][$scope] = $items;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function addScopeItem(string $scope, string $name, string $uri): static
    {
        $this->data['scopes'][$scope][$name] = $uri;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function removeScopeItem(string $scope, string $name): static
    {
        unset($this->data['scopes'][$scope][$name]);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function removeScope(string $scope): static
    {
        unset($this->data['scopes'][$scope]);

        return $this;
    }

    public function getScope(string $scope): ?string
    {
        return $this->data['scopes'][$scope] ?? null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getScopeItem(string $scope, string $name): array
    {
        return $this->data['imports'][$scope][$name];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getAllScopes(): array
    {
        return $this->data['scopes'];
    }

    /**
     * @codeCoverageIgnore
     */
    public function setScopes(array $scopes): static
    {
        $this->data['scopes'] = $scopes;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setScopeItems(array $scopes, array $items): static
    {
        $this->data['scopes'][$scopes] = $items;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function resetScopes(): static
    {
        $this->data['scopes'] = [];

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function resetScope(string $scope): static
    {
        $this->data['scopes'][$scope] = [];

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function reset(): static
    {
        $this->resetImports();
        $this->resetScopes();

        return $this;
    }

    /**
     * render
     *
     * @param  string  $type
     * @param  int     $jsonFlags
     * @param  array   $attrs
     *
     * @return  string
     *
     * @throws  JsonException
     */
    public function render(string $type = 'importmap', int $jsonFlags = 0, array $attrs = []): string
    {
        if ($this->data['imports'] === [] && $this->data['scopes'] === []) {
            return '';
        }

        if ($this->isDebug) {
            $jsonFlags |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        }

        $data = json_encode($this->data, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | $jsonFlags);

        $attrs['type'] = $type;

        if ($this->getCspNonce()) {
            $attrs['nonce'] = $this->getCspNonce();
        }

        $attrString = HTMLElement::buildAttributes($attrs);

        return <<<SCRIPT
        <script $attrString>
        $data
        </script>
        SCRIPT;
    }

    /**
     * @return  bool
     *
     * @codeCoverageIgnore
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * @param  bool  $isDebug
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function setIsDebug(bool $isDebug): static
    {
        $this->isDebug = $isDebug;

        return $this;
    }

    /**
     * @return  string
     *
     * @codeCoverageIgnore
     */
    public function getCspNonce(): string
    {
        return $this->cspNonce;
    }

    /**
     * @param  string  $cspNonce
     *
     * @return  static  Return self to support chaining.
     *
     * @codeCoverageIgnore
     */
    public function setCspNonce(string $cspNonce): static
    {
        $this->cspNonce = $cspNonce;

        return $this;
    }
}
