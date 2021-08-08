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
use Windwalker\Utilities\Paths\PathsAwareTrait;
use Windwalker\Utilities\Str;

/**
 * The Lang class.
 *
 * @method $this load(string $file, ?string $format = 'ini', ?string $locale = null, array $options = [])
 * @method string resolveNamespace(string $key)
 * @method array find(string $id, ?string $locale = null, bool $fallback = true)
 * @method string[] get(string $id, ?string $locale = null, bool $fallback = true)
 * @method string trans(string $id, ...$args)
 * @method string choice(string $id, int|float $number, ...$args)
 * @method string replace(string $string, array $args = [])
 * @method bool has(string $id, ?string $locale = null, bool $fallback = true)
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
class LangService
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

    public function __invoke(string $id, ...$args): string
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

        if ($locale === $fallback || $this->isDebug()) {
            $this->loadLanguageFile("$path/$fallback/$file.$format", $format, $fallback);
        }

        if ($locale !== $fallback) {
            $this->loadLanguageFile("$path/$locale/$file.$format", $format, $locale);
        }

        return $this;
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
        if (!class_exists(Language::class)) {
            throw new \DomainException('Please install windwalker/language first.');
        }

        $language = new Language(
            $this->config->getDeep('language.locale') ?: 'en-US',
            $this->config->getDeep('language.fallback') ?: 'en-US'
        );

        $debug = $this->config->getDeep('app.debug') ?? false;
        $langDebug = $this->config->getDeep('language.debug') ?? false;

        $language->setDebug($debug && $langDebug);

        return $language;
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
}
