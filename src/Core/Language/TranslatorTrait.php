<?php

declare(strict_types=1);

namespace Windwalker\Core\Language;

use Windwalker\DI\Attributes\Inject;
use Windwalker\Utilities\Wrapper\RawWrapper;

/**
 * The TranslatorTrait class.
 */
trait TranslatorTrait
{
    #[Inject]
    protected LangService $lang;

    /**
     * @codeCoverageIgnore
     */
    public function useLangNamespace(string $ns): LangService
    {
        return $this->lang = $this->lang->extract($ns);
    }

    /**
     * @codeCoverageIgnore
     */
    public function trans(string|RawWrapper $id, ...$args): string
    {
        return $this->lang->trans($id, ...$args);
    }

    /**
     * @codeCoverageIgnore
     */
    public function choice(string|RawWrapper $id, int|float $number, ...$args): string
    {
        return $this->lang->choice($id, $number, ...$args);
    }

    /**
     * @codeCoverageIgnore
     */
    public function hasLang(string $id, ?string $locale = null, bool $fallback = true): bool
    {
        return $this->lang->has($id, $locale, $fallback);
    }

    /**
     * @return  LangService
     *
     * @codeCoverageIgnore
     */
    public function getLang(): LangService
    {
        return $this->lang;
    }
}
