<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use Exception;
use FilesystemIterator;
use JetBrains\PhpStorm\ArrayShape;
use OutOfRangeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Asset\Event\AssetBeforeRender;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Utilities\Base64Url;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventEmitter;
use Windwalker\Filesystem\Path;
use Windwalker\Uri\Uri;
use Windwalker\Uri\UriNormalizer;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
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
    use CoreEventAwareTrait;
    use InstanceCacheTrait;

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
     * @var  AssetItem[]
     */
    protected array $internalStyles = [];

    /**
     * Property internalScripts.
     *
     * @var  AssetItem[]
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
    protected string $path = '';

    /**
     * Property root.
     *
     * @var  string
     */
    protected string $root = '';

    /**
     * @var Teleport[]
     */
    protected array $teleports = [];

    /**
     * @var ImportMap|null
     */
    protected ?ImportMap $importMap = null;

    /**
     * AssetManager constructor.
     *
     * @param  Config        $config
     * @param  SystemUri     $systemUri
     * @param  PathResolver  $pathResolver
     * @param  EventEmitter  $dispatcher
     */
    public function __construct(
        protected Config $config,
        protected SystemUri $systemUri,
        protected PathResolver $pathResolver,
        EventEmitter $dispatcher
    ) {
        $folder = $config->getDeep('asset.folder') ?? 'assets';

        $this->path = $options['uri'] ?? $this->systemUri->path($folder);
        $this->root = $options['uri'] ?? $this->systemUri->root($folder);

        $this->dispatcher = $dispatcher;
    }

    /**
     * @param  string  $url
     * @param  array   $options
     * @param  array   $attrs
     *
     * @return  AssetLink
     */
    public function css(string $url, array $options = [], array $attrs = []): AssetLink
    {
        return $this->addLink('styles', $url, $options, $attrs);
    }

    public function footerCSS(string $url, array $options = [], array $attrs = []): AssetLink
    {
        $options['footer'] = true;

        return $this->addLink('styles', $url, $options, $attrs);
    }

    /**
     * @param  string  $url
     * @param  array   $options
     * @param  array   $attrs
     *
     * @return  AssetLink
     */
    public function js(string $url, array $options = [], array $attrs = []): AssetLink
    {
        return $this->addLink('scripts', $url, $options, $attrs);
    }

    public function jsAsync(string $url, array $options = [], array $attrs = []): AssetLink
    {
        $attrs['async'] = true;

        return $this->addLink('scripts', $url, $options, $attrs);
    }

    /**
     * addScript
     *
     * @param  string  $url
     * @param  array   $options
     * @param  array   $attrs
     *
     * @return  AssetLink
     */
    public function module(string $url, array $options = [], array $attrs = []): AssetLink
    {
        $attrs['type'] = 'module';

        return $this->addLink('scripts', $url, $options, $attrs);
    }

    public function moduleAsync(string $url, array $options = [], array $attrs = []): AssetLink
    {
        $attrs['type'] = 'module';
        $attrs['async'] = true;

        return $this->addLink('scripts', $url, $options, $attrs);
    }

    public function addLink(string $type, string $url, array $options = [], array $attrs = []): AssetLink
    {
        // Cache same url and options to enhance performance
        $key = "$type:$url";

        if ($options !== []) {
            $key .= ':' . json_encode($options);
        }

        if ($attrs !== []) {
            $key .= ':' . json_encode($attrs);
        }

        return $this->once(
            $key,
            function () use ($options, $url, $attrs, $type) {
                $alias = $this->resolveRawAlias($url);

                $link = $alias ?? new AssetLink($this->handleUri($url), $options);

                $link = $link->withOptions($options);

                foreach ($attrs as $name => $value) {
                    $link = $link->withAttribute($name, $value);
                }

                $this->$type[$url] = $link;

                return $link;
            }
        );
    }

    /**
     * @param  string  $content
     * @param  array   $options
     *
     * @return  static
     */
    public function internalCSS(string $content, array $options = []): static
    {
        $this->internalStyles[] = new AssetItem($content, $options);

        return $this;
    }

    public function footerInternalCSS(string $content, array $options = []): static
    {
        $options['footer'] = true;

        return $this->internalCSS($content, $options);
    }

    /**
     * @param  string  $content
     * @param  array   $options
     *
     * @return  static
     */
    public function internalJS(string $content, array $options = []): static
    {
        $this->internalScripts[] = new AssetItem($content, $options);

        return $this;
    }

    /**
     * Check assets uri exists in system and return actual path.
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
        if (static::isFullUrl($uri)) {
            return $uri;
        }

        $assetUri = $this->path;

        if (static::isFullUrl($assetUri)) {
            return rtrim($assetUri, '/') . '/' . ltrim($uri, '/');
        }

        $uri = $this->resolveAlias($uri);
        $file = $this->addSysPath($uri);

        $this->normalizeUri($uri, $assetFile, $assetMinFile);

        if (is_file($file)) {
            return $this->addAssetBase($uri, 'path');
        }

        if (!$strict) {
            if (is_file($this->addSysPath($assetFile))) {
                return $this->addAssetBase($assetFile, 'path');
            }

            if (is_file($this->addSysPath($assetMinFile))) {
                return $this->addAssetBase($assetMinFile, 'path');
            }
        }

        return false;
    }

    /**
     * @param  bool   $withInternal
     * @param  array  $internalAttrs
     * @param  bool   $footer
     *
     * @return string
     * @throws Exception
     */
    public function renderCSS(bool $withInternal = false, array $internalAttrs = [], bool $footer = false): string
    {
        $html = [];

        $event = $this->emit(
            AssetBeforeRender::class,
            [
                'assetService' => $this,
                'withInternal' => $withInternal,
                'internalAttrs' => $internalAttrs,
                'links' => $this->styles,
                'html' => $html,
                'type' => AssetBeforeRender::TYPE_CSS,
            ]
        );

        $withInternal = $event->isWithInternal();
        $html = $event->getHtml();

        foreach ($event->getLinks() as $url => $style) {
            if ($style->isFooter() !== $footer) {
                continue;
            }

            $defaultAttrs = [
                'href' => $style->getHref(),
                'rel' => 'stylesheet',
            ];

            $attrs = array_merge($defaultAttrs, $style->getAttributes());

            if ($style->getOption('version') !== false) {
                $attrs['href'] = $this->appendVersion($attrs['href']);
            }

            if ($style->getOption('sri')) {
                $attrs['integrity'] = $style->getOption('sri');
                $attrs['crossorigin'] = 'anonymous';
            }

            $html[] = (string) h('link', $attrs, null);
        }

        if ($withInternal && $this->internalStyles) {
            $html[] = (string) h(
                'style',
                $event->getInternalAttrs(),
                "\n" . $this->renderInternalCSS($footer) . "\n" . $this->indents
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
                'assetService' => $this,
                'withInternal' => $withInternal,
                'internalAttrs' => $internalAttrs,
                'links' => $this->scripts,
                'html' => $html,
                'type' => AssetBeforeRender::TYPE_JS,
            ]
        );

        $withInternal = $event->isWithInternal();
        $html = $event->getHtml();

        foreach ($event->getLinks() as $url => $script) {
            $defaultAttrs = [
                'src' => $script->getHref(),
            ];

            $attrs = array_merge($defaultAttrs, $script->getAttributes());

            if ($script->getOption('version') !== false) {
                $attrs['src'] = $this->appendVersion($attrs['src']);
            }

            if ($script->getOption('sri')) {
                $attrs['integrity'] = $script->getOption('sri');
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
                $event->getInternalAttrs(),
                "\n" . $this->renderInternalJS() . "\n" . $this->indents
            );
        }

        return implode("\n" . $this->indents, $html);
    }

    /**
     * @param  bool  $footer
     *
     * @return  string
     */
    public function renderInternalCSS(bool $footer = false): string
    {
        $styles = array_filter(
            $this->internalStyles,
            static fn (AssetItem $item) => $item->isFooter() === $footer
        );

        return implode("\n\n", $styles);
    }

    /**
     * renderInternalStyles
     *
     * @return  string
     */
    public function renderInternalJS(): string
    {
        return implode(";\n", $this->internalScripts);
    }

    /**
     * getVersion
     *
     * @return  string
     * @throws Exception
     */
    public function getVersion(): string
    {
        if ($this->version) {
            return $this->version;
        }

        if ($this->isDebug()) {
            return $this->version = uid();
        }

        $versionFile = $this->cacheStorage['version.file'] ??= $this->config->getDeep('asset.version_file');

        $sumFile = $this->pathResolver->resolve($versionFile);

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
     * @throws Exception
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
            return $version = md5($assetUri . $this->getAppSecret());
        }

        $time = '';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->addSysPath($assetUri),
                FilesystemIterator::FOLLOW_SYMLINKS
            )
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isLink() || $file->isDir()) {
                continue;
            }

            $time .= $file->getMTime();
        }

        return $version = md5($this->getAppSecret() . $time);
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
        if (static::isFullUrl($assetUri)) {
            return $assetUri;
        }

        $assetUri = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $assetUri), '/\\');
        $base = rtrim($this->pathResolver->resolve('@public/' . $this->getAssetFolder()), '/\\');

        if (!$base) {
            return '/';
        }

        $match = '';

        // @see http://stackoverflow.com/a/6704596
        for ($i = strlen($base) - 1; $i >= 0; $i -= 1) {
            $chunk = substr($base, $i);
            $len = strlen($chunk);

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
        return stripos($uri, 'http') === 0 || str_starts_with($uri, '/');
    }

    public static function isFullUrl(string $uri): bool
    {
        return stripos($uri, 'http') === 0;
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
    public function &getInternalStyles(): array
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
    public function &getInternalScripts(): array
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

    public function importMap(string $name, string $uri): static
    {
        $this->getImportMap()->addImport($name, $this->handleUri($uri));

        return $this;
    }

    public function setImportMaps(array $map): static
    {
        $im = $this->getImportMap();

        foreach ($map['imports'] ?? [] as $name => $item) {
            if ($item instanceof RawWrapper) {
                $item = $item();
            } else {
                $item = $this->path($item);
            }

            $im->addImport($name, $item);
        }

        foreach ($map['scopes'] ?? [] as $scope => $items) {
            foreach ($items as $name => $value) {
                $im->addScopeItem($scope, $name, $value);
            }
        }

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
        $uri = $this->resolveMapAlias($uri);

        $this->normalizeUri($uri, $name);

        return $this->resolveRawAlias($uri)['alias'] ?? $uri;
    }

    protected function resolveMapAlias(string $uri): string
    {
        if (!str_starts_with($uri, '@')) {
            return $uri;
        }

        if (!str_contains($uri, '/')) {
            return $this->getImportMap()->getImport($uri) ?? $uri;
        }

        [$base, $path] = explode('/', $uri, 2);

        $base = $this->getImportMap()->getImport($base . '/') ?? $base;
        $base = rtrim($base, '/');

        return $base . '/' . $path;
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
            $name = $alias->getHref();
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
     * @param  string  $path
     *
     * @return  string
     */
    public function handleUri(string $uri, string $path = 'path'): string
    {
        $uri = $this->resolveAlias($uri);

        // Check has .min
        // $uri = Uri::addUriBase($uri, 'path');

        if (static::isFullUrl($uri)) {
            return $uri;
        }

        $assetUri = $this->path;

        if (static::isFullUrl($assetUri)) {
            return rtrim($assetUri, '/') . '/' . ltrim($uri, '/');
        }

        $this->normalizeUri($uri, $assetFile, $assetMinFile);

        // Use uncompressed file first
        if ($this->isDebug()) {
            if (is_file($this->addSysPath($assetFile))) {
                return $this->addAssetBase($assetFile, $path);
            }

            if (is_file($this->addSysPath($assetMinFile))) {
                return $this->addAssetBase($assetMinFile, $path);
            }
        } else {
            // Use min file first
            if (is_file($this->addSysPath($assetMinFile))) {
                return $this->addAssetBase($assetMinFile, $path);
            }

            if (is_file($this->addSysPath($assetFile))) {
                return $this->addAssetBase($assetFile, $path);
            }
        }

        // All file not found, fallback to default uri.
        return $this->addAssetBase($uri, $path);
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
        $cache = $this->once(
            'uri:' . $uri,
            function () use ($uri) {
                $ext = Path::getExtension($uri);

                if (Str::endsWith($uri, '.min.' . $ext)) {
                    $assetFile = substr($uri, 0, -strlen('.min.' . $ext)) . '.' . $ext;
                    $assetMinFile = $uri;
                } else {
                    $assetMinFile = substr($uri, 0, -strlen('.' . $ext)) . '.min.' . $ext;
                    $assetFile = $uri;
                }

                return [$assetFile, $assetMinFile];
            }
        );

        [$assetFile, $assetMinFile] = $cache;

        return $cache;
    }

    /**
     * addUriBase
     *
     * @param  string  $uri
     * @param  string  $path
     *
     * @return string
     *
     * @since  3.5.22.6
     */
    public function addUriBase(string $uri, string $path = 'path'): string
    {
        if (!static::isAbsoluteUrl($uri)) {
            $uri = $this->systemUri::normalize($this->systemUri->$path . '/' . $uri);
        }

        if ($path === 'root' && $uri[0] === '/') {
            $uri = $this->systemUri->host($uri);
        }

        return $uri;
    }

    public function addAssetBase(string $uri, string $path = 'path'): string
    {
        if (!static::isAbsoluteUrl($uri)) {
            $uri = $this->systemUri::normalize($this->$path . '/' . $uri);
        }

        if ($path === 'root' && $uri[0] === '/') {
            $uri = $this->systemUri::normalize($this->systemUri->toString(Uri::FULL_HOST) . $uri);
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

                    if (!$quoteKey && preg_match('/[^0-9A-Za-z_]+/m', (string) $key) === 0) {
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
     * @throws Exception
     */
    public function path(?string $uri = null, string|bool $version = false): string
    {
        if ($version === true) {
            $version = $this->getVersion();
        }

        if ($uri !== null) {
            $uri = $this->resolveAlias($uri);

            if ($version) {
                if (str_contains($uri, '?')) {
                    $uri .= '&' . $version;
                } else {
                    $uri .= '?' . $version;
                }
            }

            return $this->path . '/' . $uri;
        }

        return UriNormalizer::ensureDir($this->path);
    }

    /**
     * Method to get property Root
     *
     * @param  string|null  $uri
     * @param  bool         $version
     *
     * @return string
     * @throws Exception
     */
    public function root(?string $uri = null, string|bool $version = false): string
    {
        if ($version === true) {
            $version = $this->getVersion();
        }

        if ($uri !== null) {
            $uri = $this->resolveAlias($uri);

            if ($version) {
                if (str_contains($uri, '?')) {
                    $uri .= '&' . $version;
                } else {
                    $uri .= '?' . $version;
                }
            }

            return $this->root . '/' . $uri;
        }

        return UriNormalizer::ensureDir($this->root);
    }

    /**
     * Method to get property AssetFolder
     *
     * @return  string
     */
    public function getAssetFolder(): string
    {
        return $this->cacheStorage['asset.folder'] ??= ($this->config->getDeep('asset.folder') ?? 'assets');
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

        $allow = [
            'path',
            'root',
        ];

        if (in_array($name, $allow)) {
            return $this->$name();
        }

        throw new OutOfRangeException(sprintf('Property %s not exists.', $name));
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
        $this->styles = [];
        $this->scripts = [];
        $this->internalStyles = [];
        $this->internalScripts = [];

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->cacheStorage['debug'] ??= $this->config->getDeep('app.debug');
    }

    public function startTeleport(string $name, array $data = [], string $profile = 'main'): static
    {
        $this->getTeleport($profile)->start($name, $data);

        return $this;
    }

    public function endTeleport(string $profile = 'main'): static
    {
        $this->getTeleport($profile)->end();

        return $this;
    }

    /**
     * @param  string  $name
     *
     * @return Teleport
     */
    public function getTeleport(string $name = 'main'): Teleport
    {
        return $this->teleports[$name] ??= new Teleport(['debug' => $this->isDebug()]);
    }

    /**
     * @param  string    $name
     * @param  Teleport  $teleport
     *
     * @return  static  Return self to support chaining.
     */
    public function setTeleport(string $name, Teleport $teleport): static
    {
        $this->teleports[$name] = $teleport;

        return $this;
    }

    /**
     * @return ImportMap
     */
    public function getImportMap(): ImportMap
    {
        return $this->importMap ??= new ImportMap($this->config->getDeep('app.debug'));
    }

    /**
     * @param  ImportMap|null  $importMap
     *
     * @return  static  Return self to support chaining.
     */
    public function setImportMap(?ImportMap $importMap): static
    {
        $this->importMap = $importMap;

        return $this;
    }

    /**
     * @return Teleport[]
     */
    public function getTeleports(): array
    {
        return $this->teleports;
    }

    /**
     * @param  Teleport[]  $teleports
     *
     * @return  static  Return self to support chaining.
     */
    public function setTeleports(array $teleports): static
    {
        $this->teleports = $teleports;

        return $this;
    }

    /**
     * @return  string
     *
     */
    protected function getAppSecret(): string
    {
        return $this->cacheStorage['app.secret']
            ??= (string) Base64Url::decode((string) $this->config->getDeep('app.secret'));
    }
}
