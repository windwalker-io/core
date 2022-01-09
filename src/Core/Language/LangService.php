<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Language;

use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Runtime\Config;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;
use Windwalker\Language\Language;
use Windwalker\Language\LanguageNormalizer;
use Windwalker\Utilities\Contract\LanguageInterface;
use Windwalker\Utilities\Paths\PathsAwareTrait;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\Wrapper\RawWrapper;

/**
 * The Lang class.
 *
 * @method $this load(string $file, ?string $format = 'ini', ?string $locale = null, array $options = [])
 * @method string resolveNamespace(string $key)
 * @method array find(string $id, ?string $locale = null, bool $fallback = true)
 * @method $this addString(string $key, string $string, ?string $locale = null)
 * @method $this addStrings(array $strings, ?string $locale = null)
 * @method $this setDebug(bool $debug)
 * @method array getOrphans()
 * @method array getUsed()
 * @method string getLocale()
 * @method $this setLocale(string $locale)
 * @method string getFallback()
 * @method $this setFallback(string $fallback)
 * @method mixed normalize(string $string)
 * @method array getStrings()
 * @method $this setStrings(array $strings)
 * @method string getNamespace()
 * @method $this setNamespace(string $namespace)
 * @method bool isDebug()
 */
class LangService implements LanguageInterface
{
    use PathsAwareTrait;

    protected array $loadedFiles = [];

    protected ?Language $language = null;

    protected ?LangService $parent = null;

    /**
     * @inheritDoc
     */
    public function __construct(
        protected Config $config,
        protected PathResolver $pathResolver,
        ?Language $language = null
    ) {
        $this->language = $language;

        $this->addPath($this->config->getDeep('language.paths') ?? []);
    }

    public function __invoke(string|RawWrapper $id, ...$args): string
    {
        return $this->trans($id, ...$args);
    }

    public function loadFile(string $file, string $format = 'php', ?string $locale = null): static
    {
        $locale = $locale ?? $this->getLocale();

        foreach ($this->getClonedPaths() as $path) {
            $this->loadFileFromPath($path, $file, $format, $locale);
        }

        return $this;
    }

    public function loadFileFromPath(string $path, string $file, string $format = 'php', ?string $locale = null): static
    {
        $locale = $locale ?? $this->getLocale();
        $fallback = $this->getFallback();

        $locale = LanguageNormalizer::normalizeLangCode($locale);
        $fallback = LanguageNormalizer::normalizeLangCode($fallback);

        $path = $this->pathResolver->resolve($path);

        if (Path::getExtension($file) === $format) {
            $file = Str::removeRight($file, '.' . $format);
        }

        if ($locale === $fallback || !$this->isDebug()) {
            $this->loadLanguageFile("$path/$fallback/$file.$format", $format, $fallback);
        }

        if ($locale !== $fallback) {
            $this->loadLanguageFile("$path/$locale/$file.$format", $format, $locale);
        }

        return $this;
    }

    public function loadFileFromVendor(
        string $vendor,
        string $file,
        string $format = 'php',
        ?string $locale = null
    ): static {
        $path = "@vendor/{$vendor}/resources/languages";

        return $this->loadFileFromPath($path, $file, $format, $locale);
    }

    public function loadAllFromPath(string $path, string $format, ?string $locale = null): static
    {
        $locale = $locale ?? $this->getLocale();
        $fallback = $this->getFallback();

        $locale = LanguageNormalizer::normalizeLangCode($locale);
        $fallback = LanguageNormalizer::normalizeLangCode($fallback);

        $localePath = $this->pathResolver->resolve($path . '/' . $locale);

        $files = [];

        if (is_dir($localePath)) {
            $files = array_merge($files, iterator_to_array(Filesystem::files($localePath, false)));
        }

        $defaultPath = $this->pathResolver->resolve($path . '/' . $fallback);

        if (is_dir($defaultPath)) {
            $files = array_merge($files, iterator_to_array(Filesystem::files($defaultPath, false)));
        }

        /** @var FileObject $file */
        foreach ($files as $file) {
            $ext = $file->getExtension();

            if (strcasecmp($ext, $format) !== 0) {
                continue;
            }

            $this->loadFileFromPath($path, Path::stripExtension($file->getBasename()), strtolower($format), $locale);
        }

        return $this;
    }

    public function loadAllFromVendor(string $vendor, string $format, ?string $locale = null): static
    {
        $path = "@vendor/{$vendor}/resources/languages";

        return $this->loadAllFromPath($path, $format, $locale);
    }

    public function loadAll(string $format = 'php', ?string $locale = null): static
    {
        foreach ($this->getClonedPaths() as $path) {
            $path = $this->pathResolver->resolve($path);

            $this->loadAllFromPath($path, $format, $locale);
        }

        return $this;
    }

    protected function loadLanguageFile(string $file, string $format, ?string $locale = null): static
    {
        if (is_file($file)) {
            $this->load($file, $format, $locale);

            if (!in_array($file, $this->loadedFiles, true)) {
                $this->loadedFiles[] = $file;
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function __clone(): void
    {
        $this->paths = clone $this->paths;
    }

    /**
     * @return Language
     */
    public function getLanguage(): Language
    {
        return $this->language ??= $this->createLanguage();
    }

    protected function createLanguage(): Language
    {
        static::checkLanguageInstalled();

        $language = new Language(
            $this->config->getDeep('language.locale') ?: 'en-US',
            $this->config->getDeep('language.fallback') ?: 'en-US'
        );

        $debug = $this->config->getDeep('app.debug') ?? false;
        $langDebug = $this->config->getDeep('language.debug') ?? false;

        $language->setDebug($debug && $langDebug);

        return $language;
    }

    public static function checkLanguageInstalled(): void
    {
        if (!class_exists(Language::class)) {
            throw new \DomainException('Please install windwalker/language first.');
        }
    }

    /**
     * @param  Language|null  $language
     *
     * @return  static  Return self to support chaining.
     */
    public function setLanguage(?Language $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * extract
     *
     * @param  string  $namespace
     *
     * @return  static
     */
    public function extract(string $namespace): static
    {
        $new = clone $this;
        $new->setLanguage($this->getLanguage()->extract($namespace));
        $new->parent = $this;

        return $new;
    }

    /**
     * @return self
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * @param  self  $parent
     *
     * @return  static  Return self to support chaining.
     */
    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function __call(string $name, array $args): mixed
    {
        $r = $this->getLanguage()->$name(...$args);

        if ($r instanceof Language) {
            return $this;
        }

        return $r;
    }

    public function get(string $id, ?string $locale = null, bool $fallback = true): array
    {
        return $this->getLanguage()->get($id, $locale, $fallback);
    }

    public function trans(string|RawWrapper $id, ...$args): string
    {
        return $this->getLanguage()->trans($id, ...$args);
    }

    public function choice(string|RawWrapper $id, float|int $number, ...$args)
    {
        return $this->getLanguage()->choice($id, $number, ...$args);
    }

    public function replace(string $string, array $args = []): string
    {
        return $this->getLanguage()->replace($string, $args);
    }

    public function has(string $id, ?string $locale = null, bool $fallback = true): bool
    {
        return $this->getLanguage()->has($id, $locale, $fallback);
    }
}
