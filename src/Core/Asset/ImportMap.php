<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use JsonException;
use Windwalker\DOM\DOMElement;

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
    public function __construct(protected bool $isDebug = false)
    {
    }

    public function addImport(string $name, string $uri): static
    {
        $this->data['imports'][$name] = $uri;

        return $this;
    }

    public function removeImport(string $name): static
    {
        unset($this->data['imports'][$name]);

        return $this;
    }

    public function getImport(string $name): ?string
    {
        return $this->data['imports'][$name] ?? null;
    }

    public function getAllImports(): array
    {
        return $this->data['imports'];
    }

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

    public function setScope(string $scope, array $items): static
    {
        $this->data['scopes'][$scope] = $items;

        return $this;
    }

    public function addScopeItem(string $scope, string $name, string $uri): static
    {
        $this->data['scopes'][$scope][$name] = $uri;

        return $this;
    }

    public function removeScopeItem(string $scope, string $name): static
    {
        unset($this->data['scopes'][$scope][$name]);

        return $this;
    }

    public function removeScope(string $scope): static
    {
        unset($this->data['scopes'][$scope]);

        return $this;
    }

    public function getScope(string $scope): ?string
    {
        return $this->data['scopes'][$scope] ?? null;
    }

    public function getScopeItem(string $scope, string $name): array
    {
        return $this->data['imports'][$scope][$name];
    }

    public function getAllScopes(): array
    {
        return $this->data['scopes'];
    }

    public function setScopes(array $scopes): static
    {
        $this->data['scopes'] = $scopes;

        return $this;
    }

    public function setScopeItems(array $scopes, array $items): static
    {
        $this->data['scopes'][$scopes] = $items;

        return $this;
    }

    public function resetScopes(): static
    {
        $this->data['scopes'] = [];

        return $this;
    }

    public function resetScope(string $scope): static
    {
        $this->data['scopes'][$scope] = [];

        return $this;
    }

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
     * @throws JsonException
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

        $attrString = DOMElement::buildAttributes($attrs);

        return <<<SCRIPT
        <script $attrString>
        $data
        </script>
        SCRIPT;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * @param  bool  $isDebug
     *
     * @return  static  Return self to support chaining.
     */
    public function setIsDebug(bool $isDebug): static
    {
        $this->isDebug = $isDebug;

        return $this;
    }

    /**
     * @return string
     */
    public function getCspNonce(): string
    {
        return $this->cspNonce;
    }

    /**
     * @param  string  $cspNonce
     *
     * @return  static  Return self to support chaining.
     */
    public function setCspNonce(string $cspNonce): static
    {
        $this->cspNonce = $cspNonce;

        return $this;
    }
}
