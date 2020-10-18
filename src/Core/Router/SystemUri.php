<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Http\Uri;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\Str;

/**
 * The SystemUri class.
 *
 * @property-read string $full
 * @property-read string $current
 * @property-read string $script
 * @property-read string $root
 * @property-read string $host
 * @property-read string $path
 * @property-read string $route
 *
 * @method string full()
 * @method string current()
 * @method string script($uri = null)
 * @method string root($uri = null)
 * @method string route()
 * @method string host($uri = null)
 * @method string path($uri = null)
 * @method string scheme()
 */
class SystemUri extends Uri implements \JsonSerializable
{
    use InstanceCacheTrait;

    protected ?Uri $origin = null;

    protected ?string $scriptName = null;

    public static function parseFromRequest(ServerRequestInterface $request): static
    {
        $server = $request->getServerParams();
        /** @var static $uri */
        $uri = static::getSystemUri($request);

        // Get the host and path from the URI.
        $path   = rtrim($uri->getPath(), '/\\');
        $script = trim($server['SCRIPT_NAME'] ?? '', '/');

        // Check if the path includes "index.php".
        if ($script && str_starts_with($path, $script)) {
            // Remove the index.php portion of the path.
            $path = substr_replace($path, '', strpos($path, $script), strlen($script));
            $path = rtrim($path, '/\\');
        }

        $uri->withPath($path);
        $uri = $uri->withScriptName(pathinfo($script, PATHINFO_BASENAME));

        return $uri;
    }

    protected static function getSystemUri(ServerRequestInterface $request): static
    {
        $uri           = new static((string) $request->getUri());
        $uri->original = (string) $uri;

        $server = $request->getServerParams();

        // If we are working from a CGI SAPI with the 'cgi.fix_pathinfo' directive disabled we use PHP_SELF.
        if (str_contains(PHP_SAPI, 'cgi') && !ini_get('cgi.fix_pathinfo') && !empty($server['REQUEST_URI'])) {
            // We aren't expecting PATH_INFO within PHP_SELF so this should work.
            $uri = $uri->withPath(rtrim(\dirname($server['PHP_SELF'] ?? ''), '/\\'));
        } else {
            // Pretty much everything else should be handled with SCRIPT_NAME.
            $uri = $uri->withPath(rtrim(\dirname($server['SCRIPT_NAME'] ?? ''), '/\\'));
        }

        // Clear the unused parts of the requested URI.
        return $uri->withFragment('');
    }

    /**
     * @return string|null
     */
    public function getScriptName(): ?string
    {
        return $this->scriptName;
    }

    /**
     * @param  string  $scriptName
     *
     * @return  static  Return self to support chaining.
     */
    public function withScriptName(string $scriptName): static
    {
        $new             = $this;
        $new->scriptName = $scriptName;

        return $new;
    }

    /**
     * addPrefix
     *
     * @param   string $name
     * @param   string $url
     *
     * @return  string
     */
    public function suffix(string $name, string $url): string
    {
        return $this->$name . '/' . $url;
    }

    public function absolute(string $url, bool $full = false): string
    {
        // If uri has no scheme
        if (!preg_match('#^[a-z]+\://#i', $url)) {
            // We just need the prefix since we have a path relative to the root.
            if (str_starts_with($url, '/')) {
                $url = $this->addPrefix(
                    $url,
                    $full
                        ? $this->toString(Uri::FULL_HOST)
                        : ''
                );
            } else {
                $url = $this->addPrefix(
                    $url,
                    $full
                        ? $this->toString(Uri::FULL_HOST | Uri::PATH)
                        : $this->toString(Uri::PATH)
                );
            }
        }

        return $url;
    }

    public function addPrefix(string $url, string $prefix = '/'): string
    {
        return rtrim($prefix, '/') . Str::ensureLeft($url, '/');
    }

    public static function getFields(): array
    {
        return [
            'full',
            'current',
            'script',
            'root',
            'host',
            'path',
            'route',
        ];
    }

    public function all(): array
    {
        $all = [];

        foreach (static::getFields() as $field) {
            $all[$field] = $this->__get($field);
        }

        return $all;
    }

    public function __get(string $name)
    {
        switch ($name) {
            case 'full':
                return $this->cacheStorage['full'] ??= Str::ensureRight($this->original, '/');

            case 'current':
                return $this->cacheStorage['current'] ??= Str::ensureRight(
                    Uri::wrap($this->original)
                        ->toString(
                            static::FULL_HOST | static::PATH
                        ),
                    '/'
                );

            case 'script':
                return $this->scriptName;

            case 'root':
                return $this->cacheStorage['root'] ??= Str::ensureRight(
                    $this->toString(static::FULL_HOST | static::PATH),
                    '/'
                );

            case 'host':
                return $this->cacheStorage['host'] ??= $this->toString(static::FULL_HOST);

            case 'path':
                return $this->cacheStorage['path'] ??= Str::ensureRight($this->toString(static::PATH), '/');

            case 'route':
                return $this->cacheStorage['route'] ??= (function () {
                    // Set the extended (non-base) part of the request URI as the route.
                    $route = substr_replace($this->current, '', 0, strlen($this->root));

                    $file = explode('/', $this->script);
                    $file = '/' . array_pop($file);

                    if ($file === '' || str_starts_with($route, $file)) {
                        $route = trim(substr($route, strlen($file)), '/');
                    }

                    return Str::ensureLeft(rtrim($route,'/'), '/');
                })();
        }

        return $this->$name;
    }

    /**
     * __call
     *
     * @param   string $name
     * @param   array  $args
     *
     * @return  mixed
     */
    public function __call(string $name, array $args): mixed
    {
        if (in_array($name, static::getFields())) {
            if (isset($args[0])) {
                return $this->suffix($name, $args[0]);
            }

            return $this->$name;
        }

        throw new \BadMethodCallException('Method: ' . __CLASS__ . '::' . $name . '() not found.');
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
