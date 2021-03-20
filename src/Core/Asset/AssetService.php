<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset;

use JetBrains\PhpStorm\ArrayShape;
use Windwalker\Core\Asset\Event\AssetBeforeRender;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Runtime\Config;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Event\EventEmitter;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\Wrapper\RawWrapper;

use function Windwalker\DOM\h;
use function Windwalker\uid;

/**
 * The AssetManager class.
 *
 * @since   3.0
 */
class AssetService implements EventAwareInterface
{
    use EventAwareTrait;

    /**
     * Property styles.
     *
     * @var  AssetLink[]
     */
    protected array $styles = [];

    /**
     * Property scripts.
     *
     * @var  AssetLink[]
     */
    protected array $scripts = [];

    /**
     * Property aliases.
     *
     * @var  AssetLink[]
     */
    protected array $aliases = [];

    /**
     * Property internalStyles.
     *
     * @var  array
     */
    protected array $internalStyles = [];

    /**
     * Property internalScripts.
     *
     * @var  array
     */
    protected array $internalScripts = [];

    /**
     * Property version.
     *
     * @var string
     */
    protected ?string $version = null;

    /**
     * Property indents.
     *
     * @var  string
     */
    protected string $indents = '    ';

    /**
     * Property path.
     *
     * @var  string
     */
    public string $path = '';

    /**
     * Property root.
     *
     * @var  string
     */
    public string $root = '';

    protected ?Teleport $teleport = null;

    /**
     * AssetManager constructor.
     *
     * @param  Config        $config
     * @param  SystemUri     $systemUri
     * @param  EventEmitter  $dispatcher
     */
    public function __construct(
        protected Config $config,
        protected SystemUri $systemUri,
        EventEmitter $dispatcher
    ) {
        $this->path = $options['uri'] ?? $this->systemUri->path($options['folder'] ?? 'asset');
        $this->root = $options['uri'] ?? $this->systemUri->root($options['folder'] ?? 'asset');

        $this->dispatcher = $dispatcher;
    }

    /**
     * addStyle
     *
     * @param  string  $url
     * @param  array   $options
     * @param  array   $attrs
     *
     * @return  static
     */
    public function addCSS(string $url, array $options = [], array $attrs = []): static
    {
        return $this->addLink('styles', $url, $options, $attrs);
    }

    /**
     * addScript
     *
     * @param  string  $url
     * @param  array   $options
     * @param  array   $attrs
     *
     * @return  static
     */
    public function addJS(string $url, array $options = [], array $attrs = []): static
    {
        return $this->addLink('scripts', $url, $options, $attrs);
    }

    /**
     * addScript
     *
     * @param  string  $url
     * @param  array   $options
     * @param  array   $attrs
     *
     * @return  static
     */
    public function addModule(string $url, array $options = [], array $attrs = []): static
    {
        $attrs['type'] = 'module';

        return $this->addLink('scripts', $url, $options, $attrs);
    }

    public function addLink(string $type, string $url, array $options = [], array $attrs = []): static
    {
        $alias = $this->resolveRawAlias($url);

        $link = $alias ?? new AssetLink($this->handleUri($url), $options);

        $link = $link->withOptions($options);

        foreach ($attrs as $name => $value) {
            $link = $link->withAttribute($name, $value);
        }

        $this->$type[$url] = $link;

        return $this;
    }

    /**
     * internalStyle
     *
     * @param  string  $content
     *
     * @return  static
     */
    public function internalCSS(string $content): static
    {
        $this->internalStyles[] = $content;

        return $this;
    }

    /**
     * internalStyle
     *
     * @param  string  $content
     *
     * @return  static
     */
    public function internalJS(string $content): static
    {
        $this->internalScripts[] = $content;

        return $this;
    }

    /**
     * Check asset uri exists in system and return actual path.
     *
     * @param  string  $uri     The file uri to check.
     * @param  bool    $strict  Check .min file or un-compress file exists again if input file not exists.
     *
     * @return  bool|string
     *
     * @since  3.3
     */
    public function has(string $uri, bool $strict = false): bool|string
    {
        if (static::isAbsoluteUrl($uri)) {
            return $uri;
        }

        $assetUri = $this->path;

        if (static::isAbsoluteUrl($assetUri)) {
            return rtrim($assetUri, '/') . '/' . ltrim($uri, '/');
        }

        $root = $this->addSysPath($assetUri);

        $this->normalizeUri($uri, $assetFile, $assetMinFile);

        if (is_file($root . '/' . $uri)) {
            return $this->addBase($uri, 'path');
        }

        if (!$strict) {
            if (is_file($root . '/' . $assetFile)) {
                return $this->addBase($assetFile, 'path');
            }

            if (is_file($root . '/' . $assetMinFile)) {
                return $this->addBase($assetMinFile, 'path');
            }
        }

        return false;
    }

    /**
     * renderStyles
     *
     * @param  bool   $withInternal
     * @param  array  $internalAttrs
     *
     * @return string
     * @throws \Exception
     */
    public function renderCSS(bool $withInternal = false, array $internalAttrs = []): string
    {
        $html = [];

        $event = $this->emit(
            AssetBeforeRender::class,
            [
                'asset' => $this,
                'withInternal' => $withInternal,
                'html' => $html,
                'type' => AssetBeforeRender::TYPE_JS,
            ]
        );

        $withInternal = $event->isWithInternal();
        $html         = $event->getHtml();

        foreach ($this->styles as $url => $style) {
            $defaultAttrs = [
                'href' => $style->getHref(),
                'rel' => 'stylesheet',
            ];

            $attrs = array_merge($defaultAttrs, $style->getAttributes());

            if ($style->getOption('version') !== false) {
                $attrs['href'] = $this->appendVersion($attrs['href']);
            }

            if ($style->getOption('sri')) {
                $attrs['integrity']   = $style->getOption('sri');
                $attrs['crossorigin'] = 'anonymous';
            }

            $html[] = (string) h('link', $attrs, null);
        }

        if ($withInternal && $this->internalStyles) {
            $html[] = (string) h(
                'style',
                $internalAttrs,
                "\n" . $this->renderInternalJS() . "\n" . $this->indents
            );
        }

        return implode("\n" . $this->indents, $html);
    }

    /**
     * renderStyles
     *
     * @param  bool   $withInternal
     * @param  array  $internalAttrs
     *
     * @return string
     */
    public function renderJS(bool $withInternal = false, array $internalAttrs = []): string
    {
        $html = [];

        $event = $this->emit(
            AssetBeforeRender::class,
            [
                'asset' => $this,
                'withInternal' => $withInternal,
                'html' => $html,
                'type' => AssetBeforeRender::TYPE_JS,
            ]
        );

        $withInternal = $event->isWithInternal();
        $html         = $event->getHtml();

        foreach ($this->scripts as $url => $script) {
            $defaultAttrs = [
                'src' => $script['url'],
            ];

            $attrs = array_merge($defaultAttrs, $script->getAttributes());

            if ($script->getOption('version') !== false) {
                $attrs['src'] = $this->appendVersion($attrs['src']);
            }

            if (isset($script['options']['sri'])) {
                $attrs['integrity']   = $script['options']['sri'];
                $attrs['crossorigin'] = 'anonymous';
            }
            if ($script->getOption('body')) {
                $content = $script->getOption('body');

                unset($attrs['src']);
            } else {
                $content = null;
            }

            $html[] = (string) h('script', $attrs, $content);
        }

        if ($withInternal && $this->internalScripts) {
            $html[] = (string) h(
                'script',
                $internalAttrs,
                "\n" . $this->renderInternalCSS() . "\n" . $this->indents
            );
        }

        return implode("\n" . $this->indents, $html);
    }

    /**
     * renderInternalStyles
     *
     * @return  string
     */
    public function renderInternalJS(): string
    {
        return implode("\n\n", $this->internalStyles);
    }

    /**
     * renderInternalStyles
     *
     * @return  string
     */
    public function renderInternalCSS(): string
    {
        return implode(";\n", $this->internalScripts);
    }

    /**
     * getVersion
     *
     * @return  string
     * @throws \Exception
     */
    public function getVersion(): string
    {
        if ($this->version) {
            return $this->version;
        }

        if ($this->config->getDeep('app.debug')) {
            return $this->version = uid();
        }

        $sumFile = $this->config->path('@cache/asset/MD5SUM');

        if (!is_file($sumFile)) {
            return $this->version = $this->detectVersion();
        }

        return $this->version = trim(file_get_contents($sumFile));
    }

    /**
     * appendVersion
     *
     * @param  string       $uri
     * @param  string|null  $version
     *
     * @return  string
     *
     * @throws \Exception
     * @since  3.4.9.3
     */
    public function appendVersion(string $uri, ?string $version = null): string
    {
        $version = $version ?: $this->getVersion();

        if (!$version) {
            return $uri;
        }

        $sep = str_contains($uri, '?') ? '&' : '?';

        return $uri . $sep . $version;
    }

    /**
     * detectVersion
     *
     * @return  string
     */
    protected function detectVersion(): string
    {
        static $version;

        if ($version) {
            return $version;
        }

        $assetUri = $this->path;

        if (static::isAbsoluteUrl($assetUri)) {
            return $version = md5($assetUri . $this->config->getDeep('app.secret'));
        }

        $time  = '';
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->addSysPath($assetUri),
                \FilesystemIterator::FOLLOW_SYMLINKS
            )
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isLink() || $file->isDir()) {
                continue;
            }

            $time .= $file->getMTime();
        }

        return $version = md5($this->config->getDeep('app.secret') . $time);
    }

    /**
     * removeBase
     *
     * @param  string  $assetUri
     *
     * @return  string
     */
    public function addSysPath(string $assetUri): string
    {
        if (static::isAbsoluteUrl($assetUri)) {
            return $assetUri;
        }

        $assetUri = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $assetUri), '/\\');
        $base     = rtrim($this->config->path('@public'), '/\\');

        if (!$base) {
            return '/';
        }

        $match = '';

        // @see http://stackoverflow.com/a/6704596
        for ($i = strlen($base) - 1; $i >= 0; $i -= 1) {
            $chunk = substr($base, $i);
            $len   = strlen($chunk);

            if (str_starts_with($assetUri, $chunk) && $len > strlen($match)) {
                $match = $chunk;
            }
        }

        return $base . DIRECTORY_SEPARATOR . ltrim(substr($assetUri, strlen($match)), '/\\');
    }

    /**
     * isAbsoluteUrl
     *
     * @param  string  $uri
     *
     * @return  boolean
     */
    public static function isAbsoluteUrl(string $uri): bool
    {
        return stripos($uri, 'http') === 0 || str_starts_with($uri, '//');
    }

    /**
     * Method to set property version
     *
     * @param  string  $version
     *
     * @return  static  Return self to support chaining.
     */
    public function setVersion(string $version): string
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Method to get property Styles
     *
     * @return  AssetLink[]
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * Method to set property styles
     *
     * @param  array  $styles
     *
     * @return  static  Return self to support chaining.
     */
    public function setStyles(array $styles): static
    {
        $this->styles = $styles;

        return $this;
    }

    /**
     * Method to get property Scripts
     *
     * @return  array
     */
    public function &getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * Method to set property scripts
     *
     * @param  array  $scripts
     *
     * @return  static  Return self to support chaining.
     */
    public function setScripts(array $scripts): static
    {
        $this->scripts = $scripts;

        return $this;
    }

    /**
     * Method to get property InternalStyles
     *
     * @return  array
     */
    public function getInternalStyles(): array
    {
        return $this->internalStyles;
    }

    /**
     * Method to set property internalStyles
     *
     * @param  array  $internalStyles
     *
     * @return  static  Return self to support chaining.
     */
    public function setInternalStyles(array $internalStyles): static
    {
        $this->internalStyles = $internalStyles;

        return $this;
    }

    /**
     * Method to get property InternalScripts
     *
     * @return  array
     */
    public function getInternalScripts(): array
    {
        return $this->internalScripts;
    }

    /**
     * Method to set property internalScripts
     *
     * @param  array  $internalScripts
     *
     * @return  static  Return self to support chaining.
     */
    public function setInternalScripts(array $internalScripts): static
    {
        $this->internalScripts = $internalScripts;

        return $this;
    }

    /**
     * alias
     *
     * @param  string  $target
     * @param  string  $alias
     * @param  array   $options
     * @param  array   $attrs
     *
     * @return  static
     */
    public function alias(string $target, string $alias, array $options = [], array $attrs = []): static
    {
        $this->normalizeUri($target, $name);

        $link = new AssetLink($alias, $options);
        $link = $link->withAttributes($attrs);

        $this->aliases[$name] = $link;

        return $this;
    }

    /**
     * resolveAlias
     *
     * @param  string  $uri
     *
     * @return  string
     */
    public function resolveAlias(string $uri): string
    {
        $this->normalizeUri($uri, $name);

        return $this->resolveRawAlias($uri)['alias'] ?? $uri;
    }

    /**
     * resolveRawAlias
     *
     * @param  string  $uri
     *
     * @return  array|null
     *
     * @since  3.5.5
     */
    public function resolveRawAlias(string $uri): ?AssetLink
    {
        $this->normalizeUri($uri, $name);

        while (isset($this->aliases[$name])) {
            $alias = $this->aliases[$name];
            $name  = $alias->getHref();
        }

        return $alias ?? null;
    }

    /**
     * Method to set property indents
     *
     * @param  string  $indents
     *
     * @return  static  Return self to support chaining.
     */
    public function setIndents(string $indents): static
    {
        $this->indents = $indents;

        return $this;
    }

    /**
     * Method to get property Indents
     *
     * @return  string
     */
    public function getIndents(): string
    {
        return $this->indents;
    }

    /**
     * handleUri
     *
     * @param  string  $uri
     *
     * @return  string
     */
    public function handleUri(string $uri): string
    {
        $uri = $this->resolveAlias($uri);

        // Check has .min
        // $uri = Uri::addBase($uri, 'path');

        if (static::isAbsoluteUrl($uri)) {
            return $uri;
        }

        $assetUri = $this->path;

        if (static::isAbsoluteUrl($assetUri)) {
            return rtrim($assetUri, '/') . '/' . ltrim($uri, '/');
        }

        $root = $this->addSysPath($assetUri);

        $this->normalizeUri($uri, $assetFile, $assetMinFile);

        // Use uncompressed file first
        if ($this->isDebug()) {
            if (is_file($root . '/' . $assetFile)) {
                return $this->addBase($assetFile, 'path');
            }

            if (is_file($root . '/' . $assetMinFile)) {
                return $this->addBase($assetMinFile, 'path');
            }
        } else {
            // Use min file first
            if (is_file($root . '/' . $assetMinFile)) {
                return $this->addBase($assetMinFile, 'path');
            }

            if (is_file($root . '/' . $assetFile)) {
                return $this->addBase($assetFile, 'path');
            }
        }

        // All file not found, fallback to default uri.
        return $this->addBase($uri, 'path');
    }

    /**
     * normalizeUri
     *
     * @param  string       $uri
     * @param  string|null  $assetFile
     * @param  string|null  $assetMinFile
     *
     * @return  array
     */
    #[ArrayShape(['string', 'string'])]
    public function normalizeUri(
        string $uri,
        ?string &$assetFile = null,
        ?string &$assetMinFile = null
    ): array {
        $ext = Path::getExtension($uri);

        if (Str::endsWith($uri, '.min.' . $ext)) {
            $assetFile    = substr($uri, 0, -strlen('.min.' . $ext)) . '.' . $ext;
            $assetMinFile = $uri;
        } else {
            $assetMinFile = substr($uri, 0, -strlen('.' . $ext)) . '.min.' . $ext;
            $assetFile    = $uri;
        }

        return [$assetFile, $assetMinFile];
    }

    /**
     * addBase
     *
     * @param  string  $uri
     * @param  string  $path
     *
     * @return  string
     */
    public function addBase(string $uri, string $path = 'path'): string
    {
        if (!static::isAbsoluteUrl($uri)) {
            $uri = $this->$path . '/' . $uri;
        }

        return $uri;
    }

    /**
     * addUriBase
     *
     * @param  string  $uri
     * @param  string  $path
     *
     * @return  mixed|string
     *
     * @since  3.5.22.6
     */
    public function addUriBase($uri, $path = 'path')
    {
        if (!static::isAbsoluteUrl($uri)) {
            $uri = $this->uri->$path . '/' . $uri;
        }

        return $uri;
    }

    /**
     * Internal method to get a JavaScript object notation string from an array
     *
     * @param  mixed  $data      The data to convert to JavaScript object notation
     * @param  bool   $quoteKey  Quote json key or not.
     *
     * @return string JavaScript object notation representation of the array
     */
    public static function getJSObject(mixed $data, bool $quoteKey = false): string
    {
        if ($data === null) {
            return 'null';
        }

        if ($data instanceof RawWrapper) {
            return $data();
        }

        $output = '';

        switch (gettype($data)) {
            case 'boolean':
                $output .= $data ? 'true' : 'false';
                break;

            case 'float':
            case 'double':
            case 'integer':
                $output .= $data + 0;
                break;

            case 'array':
                if (array_is_list($data)) {
                    $child = [];

                    foreach ($data as $value) {
                        $child[] = static::getJSObject($value, $quoteKey);
                    }

                    $output .= '[' . implode(',', $child) . ']';
                    break;
                }
            // No break
            case 'object':
                $array = is_object($data) ? get_object_vars($data) : $data;

                $row = [];

                foreach ($array as $key => $value) {
                    $encodedKey = json_encode((string) $key);

                    if (!$quoteKey && preg_match('/[^0-9A-Za-z_]+/m', $key) === 0) {
                        $encodedKey = substr(substr($encodedKey, 0, -1), 1);
                    }

                    $row[] = $encodedKey . ':' . static::getJSObject($value, $quoteKey);
                }

                $output .= '{' . implode(',', $row) . '}';
                break;

            default:  // anything else is treated as a string
                return str_starts_with($data, '\\') ? substr($data, 1) : json_encode($data);
                break;
        }

        return $output;
    }

    /**
     * Method to get property Path
     *
     * @param  string|null  $uri
     * @param  bool         $version
     *
     * @return string
     * @throws \Exception
     */
    public function path(?string $uri = null, string|bool $version = false)
    {
        if ($version === true) {
            $version = $this->getVersion();
        }

        if ($version) {
            if (str_contains($uri, '?')) {
                $uri .= '&' . $version;
            } else {
                $uri .= '?' . $version;
            }
        }

        if ($uri !== null) {
            return $this->path . '/' . $uri;
        }

        return $this->path;
    }

    /**
     * Method to get property Root
     *
     * @param  string|null  $uri
     * @param  bool         $version
     *
     * @return string
     * @throws \Exception
     */
    public function root(?string $uri = null, string|bool $version = false)
    {
        if ($version === true) {
            $version = $this->getVersion();
        }

        if ($version) {
            if (str_contains($uri, '?')) {
                $uri .= '&' . $version;
            } else {
                $uri .= '?' . $version;
            }
        }

        if ($uri !== null) {
            return $this->root . '/' . $uri;
        }

        return $this->root;
    }

    /**
     * Method to get property AssetFolder
     *
     * @return  string
     */
    public function getAssetFolder(): string
    {
        return $this->config->getDeep('asset.folder', 'asset');
    }

    /**
     * __get
     *
     * @param  string  $name
     *
     * @return  mixed
     */
    public function __get($name)
    {
        $allow = [
            'uri',
        ];

        if (in_array($name, $allow)) {
            return $this->$name;
        }

        throw new \OutOfRangeException(sprintf('Property %s not exists.', $name));
    }

    /**
     * reset
     *
     * @return  static
     *
     * @since  3.5.13
     */
    public function reset(): self
    {
        $this->styles          = [];
        $this->scripts         = [];
        $this->internalStyles  = [];
        $this->internalScripts = [];

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->config->getDeep('app.debug');
    }

    /**
     * @return Teleport|null
     */
    public function getTeleport(): ?Teleport
    {
        return $this->teleport ??= new Teleport(['debug' => $this->isDebug()]);
    }

    /**
     * @param  Teleport  $teleport
     *
     * @return  static  Return self to support chaining.
     */
    public function setTeleport(Teleport $teleport): static
    {
        $this->teleport = $teleport;

        return $this;
    }
}
