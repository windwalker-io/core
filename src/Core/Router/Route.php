<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use http\Encoding\Stream;
use Windwalker\Utilities\Str;

/**
 * The Route class.
 */
class Route
{
    /**
     * Property name.
     *
     * @var  string
     */
    protected ?string $name = null;

    /**
     * Property pattern.
     *
     * @var  string
     */
    protected ?string $pattern = null;

    /**
     * Property allowMethods.
     *
     * @var  array
     */
    protected array $allowMethods = [];

    /**
     * Property variables.
     *
     * @var  array
     */
    protected array $vars = [];

    /**
     * Property host.
     *
     * @var string
     */
    protected ?string $host = null;

    /**
     * Property scheme.
     *
     * @var  string
     */
    protected  ?string $scheme = null;

    /**
     * Property port.
     *
     * @var int
     */
    protected ?int $port = null;

    /**
     * Property sslPort.
     *
     * @var int
     */
    protected ?int $sslPort = null;

    /**
     * Property options.
     *
     * @var  array
     */
    protected array $options = [];

    /**
     * Property ssl.
     *
     * @var boolean
     */
    protected bool $ssl = false;

    /**
     * Property extra.
     *
     * @var  array
     */
    protected array $extra = [];

    /**
     * Class init.
     *
     * @param string       $name
     * @param string       $pattern
     * @param array        $options
     */
    public function __construct(string $name, string $pattern, array $options = [])
    {
        $this->name = $name;

        $this->setPattern($pattern);
        $this->setOptions($options);
    }

    /**
     * getPattern
     *
     * @return  string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * setPattern
     *
     * @param   string $pattern
     *
     * @return  Route  Return self to support chaining.
     */
    public function setPattern(string $pattern): static
    {
        $this->pattern = static::normalise($pattern);

        return $this;
    }

    public static function normalise(string $route): string
    {
        return '/' . ltrim($route, '/');
    }

    /**
     * getMethod
     *
     * @return  array
     */
    public function getAllowMethods(): array
    {
        return $this->allowMethods;
    }

    /**
     * setMethod
     *
     * @param   array|string $methods
     *
     * @return  Route  Return self to support chaining.
     */
    public function setAllowMethods(array|string $methods): static
    {
        $methods = (array) $methods;

        $methods = array_map('strtoupper', $methods);

        $this->allowMethods = $methods;

        return $this;
    }

    /**
     * getVariables
     *
     * @return  array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * setVariables
     *
     * @param   array  $vars
     *
     * @return  Route  Return self to support chaining.
     */
    public function setVars(array $vars): static
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * getName
     *
     * @return  string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * setName
     *
     * @param   string $name
     *
     * @return  Route  Return self to support chaining.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Method to get property Options
     *
     * @return  array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Method to set property options
     *
     * @param   array $options
     *
     * @return  static  Return self to support chaining.
     */
    public function setOptions(array $options): static
    {
        $options = $this->prepareOptions($options);

        $this->setHost($options['host']);
        $this->setScheme($options['scheme']);
        $this->setPort($options['port']);
        $this->setSslPort($options['sslPort']);
        $this->setExtraValues($options['extra']);

        return $this;
    }

    /**
     * prepareOptions
     *
     * @param   array $options
     *
     * @return  array
     */
    public function prepareOptions(array $options): array
    {
        $defaultOptions = [
            'options' => [],
            'host' => null,
            'scheme' => null,
            'port' => 80,
            'sslPort' => 443,
            'extra' => [],
        ];

        return array_merge($defaultOptions, (array) $options);
    }

    /**
     * Method to get property Options
     *
     * @param   string $name
     * @param   mixed  $default
     *
     * @return  mixed
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * Method to set property options
     *
     * @param   string $name
     * @param   mixed  $value
     *
     * @return  static  Return self to support chaining.
     */
    public function setOption(string $name, mixed $value): static
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Method to get property SslPort
     *
     * @return  int
     */
    public function getSslPort(): int
    {
        return $this->sslPort;
    }

    /**
     * Method to set property sslPort
     *
     * @param   int $sslPort
     *
     * @return  static  Return self to support chaining.
     */
    public function setSslPort(int $sslPort): static
    {
        $this->sslPort = (int) $sslPort;

        return $this;
    }

    /**
     * Method to get property Port
     *
     * @return  int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Method to set property port
     *
     * @param   int $port
     *
     * @return  static  Return self to support chaining.
     */
    public function setPort(int $port): static
    {
        $this->port = (int) $port;

        return $this;
    }

    /**
     * Method to get property Scheme
     *
     * @return  string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Method to set property scheme
     *
     * @param   string $scheme
     *
     * @return  static  Return self to support chaining.
     */
    public function setScheme(string $scheme): static
    {
        $this->scheme = strtolower((string) $scheme);

        $this->ssl = ($this->scheme === 'https');

        return $this;
    }

    /**
     * Method to get property Host
     *
     * @return  string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Method to set property host
     *
     * @param   string $host
     *
     * @return  static  Return self to support chaining.
     */
    public function setHost(string $host): static
    {
        $this->host = strtolower($host);

        return $this;
    }

    /**
     * Method to get property Ssl
     *
     * @return  boolean
     */
    public function getSSL(): bool
    {
        return $this->ssl;
    }

    /**
     * Method to set property ssl
     *
     * @param   boolean $ssl
     *
     * @return  static  Return self to support chaining.
     */
    public function setSSL(bool $ssl): static
    {
        $this->ssl = $ssl;

        return $this;
    }

    /**
     * Method to get property Extra
     *
     * @return  array
     */
    public function getExtraValues(): array
    {
        return $this->extra;
    }

    /**
     * Method to set property extra
     *
     * @param   array $extra
     *
     * @return  static  Return self to support chaining.
     */
    public function setExtraValues(array $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * getExtra
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return  mixed
     */
    public function getExtra(string $name, mixed $default = null): mixed
    {
        return $this->extra[$name] ?? $default;
    }

    /**
     * setExtra
     *
     * @param   string $name
     * @param   mixed  $value
     *
     * @return  static
     */
    public function setExtra(string $name, mixed $value): static
    {
        $this->extra[$name] = $value;

        return $this;
    }
}
