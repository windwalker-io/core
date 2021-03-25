<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
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

/**
 * The Lang class.
 */
class LangService extends Language
{
    use PathsAwareTrait;

    protected array $loadedFiles = [];

    /**
     * @inheritDoc
     */
    public function __construct(protected Config $config, protected PathResolver $pathResolver)
    {
        parent::__construct(
            $config->getDeep('language.locale') ?: 'en-US',
            $config->getDeep('language.fallback') ?: 'en-US'
        );

        $debug = $config->getDeep('app.debug') ?? false;
        $langDebug = $config->getDeep('language.debug') ?? false;

        $this->addPath($config->getDeep('language.paths'));

        $this->setDebug($debug && $langDebug);
    }

    public function __invoke(string $id, ...$args): string
    {
        return $this->trans($id, $args);
    }

    /**
     * @inheritDoc
     */
    public function setLocale(string $locale): static
    {
        $this->config->setDeep('language.locale', $locale);

        return parent::setLocale($locale);
    }

    /**
     * @inheritDoc
     */
    public function setFallback(string $fallback): static
    {
        $this->config->setDeep('language.fallback', $fallback);

        return parent::setFallback($fallback);
    }

    public function loadFile(string $file, string $format = 'php', ?string $locale = null): static
    {
        $locale = $locale ?? $this->getLocale();

        foreach ($this->getClonedPaths() as $path) {
            $this->loadFileFromPath($path, $file, $format, $locale);
        }

        return $this;
    }

    public function loadFileFromPath(string $path, string|array $file, string $format = 'php', ?string $locale = null): static
    {
        $locale = $locale ?? $this->getLocale();
        $fallback = $this->getFallback();

        $locale = LanguageNormalizer::normalizeLangCode($locale);
        $fallback = LanguageNormalizer::normalizeLangCode($fallback);

        $path = $this->pathResolver->resolve($path);

        if ($locale === $fallback || $this->isDebug()) {
            $this->loadLanguageFile("$path/$fallback/$file.$format", $format);
        }

        if ($locale !== $fallback) {
            $this->loadLanguageFile("$path/$locale/$file.$format", $format);
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

    protected function loadLanguageFile(string $file, string $format): static
    {
        if (is_file($file)) {
            $this->load($file, $format);

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
}
