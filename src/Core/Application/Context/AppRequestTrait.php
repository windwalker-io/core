<?php

declare(strict_types=1);

namespace Windwalker\Core\Application\Context;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\Data\Format\FormatRegistry;
use Windwalker\Filter\Exception\ValidateException;
use Windwalker\Uri\Uri;

use function Windwalker\collect;

/**
 * Trait AppRequestTrait
 */
trait AppRequestTrait
{
    protected mixed $input = null;

    protected ?string $clientIp = null;

    public protected(set) SystemUri $systemUri;

    public protected(set) ?Route $matchedRoute = null;

    protected ProxyResolver $proxyResolver;

    public function withClientIp(?string $clientIp): static
    {
        $new = clone $this;
        $new->clientIp = $clientIp;

        return $new;
    }

    public function getClientIP(): string
    {
        if ($this->clientIp !== null) {
            return $this->clientIp;
        }

        if ($this->proxyResolver->isProxy()) {
            if ($this->proxyResolver->isTrustedProxy()) {
                return $this->request->getServerParams()['REMOTE_ADDR'] ?? '';
            }

            return $this->proxyResolver->getForwardedIP();
        }

        return $this->request->getServerParams()['REMOTE_ADDR'] ?? '';
    }

    /**
     * @return Uri
     */
    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    /**
     * @param  UriInterface  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function withUri(UriInterface $uri): static
    {
        $new = clone $this;
        $new->request = $new->request->withUri($uri);

        return $new;
    }

    /**
     * getEvent
     *
     * @return  array
     */
    abstract protected function getUriQueryValues(): mixed;

    public function getQueryValues(): array
    {
        return array_merge(
            $this->getUrlVars(),
            $this->getUriQueryValues()
        );
    }

    /**
     * @return array
     */
    abstract public function getUrlVars(): array;

    /**
     * @param  array  $vars
     *
     * @return  static  Return self to support chaining.
     */
    public function withUrlVars(array $vars): static
    {
        $new = clone $this;
        $new->matchedRoute = clone $new->matchedRoute;
        $new->matchedRoute->vars($vars);

        $new->input = null;

        return $new;
    }

    /**
     * @return array
     */
    public function getInput(): array
    {
        return (array) $this->input;
    }

    /**
     * @param  array  $input
     *
     * @return  static  Return self to support chaining.
     */
    public function withInput(array $input): static
    {
        $new = clone $this;
        $new->input = $input;

        return $new;
    }

    public function getHeader(string $name): string
    {
        return $this->request->getHeaderLine($name);
    }

    public function getHeaders(bool $asString = true): array
    {
        if (!$asString) {
            return $this->request->getHeaders();
        }

        return array_map(
            static fn (array $values) => implode(',', $values),
            $this->request->getHeaders(),
        );
    }

    /**
     * input
     *
     * @param  mixed  ...$fields
     *
     * @return  mixed|Collection
     */
    public function input(mixed ...$fields): mixed
    {
        $input = $this->compileInput();

        $fields = array_filter($fields);

        if ($fields === []) {
            if (is_array($input) || is_object($input)) {
                return collect($input);
            }

            return $input;
        }

        if (!is_array($input)) {
            return $input;
        }

        return $this->fetchInputFields($input, $fields);
    }

    /**
     * @param  array  $input
     * @param  array  $fields
     *
     * @return  mixed|Collection
     */
    protected function fetchInputFields(array $input, array $fields): mixed
    {
        $data = [];

        if (array_is_list($fields)) {
            foreach ($fields as $field) {
                $data[$field] = $input[$field] ?? null;
            }
        } else {
            foreach (array_keys($fields) as $field) {
                $data[$field] = $input[$field] ?? null;
            }

            try {
                $this->getFilterFactory()->createNested($fields)->test($data);
            } catch (ValidateException $e) {
                throw new ValidateFailException(
                    $e->getMessage(),
                    400,
                    $e
                );
            }
        }

        if (count($fields) === 1) {
            return array_shift($data);
        }

        return collect($data);
    }

    abstract protected function compileInput(): mixed;

    /**
     * @return SystemUri
     */
    public function getSystemUri(): SystemUri
    {
        return $this->systemUri;
    }

    /**
     * @param  SystemUri  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function withSystemUri(SystemUri $uri): static
    {
        $new = clone $this;
        $new->systemUri = $uri;

        return $new;
    }

    /**
     * @return Route|null
     */
    public function getMatchedRoute(): ?Route
    {
        return $this->matchedRoute;
    }

    /**
     * @param  Route|null  $matchedRoute
     *
     * @return  static  Return self to support chaining.
     */
    public function withMatchedRoute(?Route $matchedRoute): static
    {
        $new = clone $this;
        $new->matchedRoute = $matchedRoute;
        $new->input = null;

        return $new;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): mixed
    {
        $req = FormatRegistry::makeDumpable($this->getRequest());
        $req['stream'] = null;

        return array_merge(
            $req,
            [
                'systemUri' => $this->getSystemUri(),
            ]
        );
    }

    /**
     * @return ProxyResolver
     */
    public function getProxyResolver(): ProxyResolver
    {
        return $this->proxyResolver;
    }

    /**
     * @return ServerRequestInterface
     *
     * @deprecated  Use getServerRequest() instead.
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
