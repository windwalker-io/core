<?php

/**
 * Part of datavideo project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

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

    public function useLangNamespace(string $ns): LangService
    {
        return $this->lang = $this->lang->extract($ns);
    }

    public function trans(string|RawWrapper $id, ...$args): string
    {
        return $this->lang->trans($id, ...$args);
    }

    public function choice(string|RawWrapper $id, int|float $number, ...$args): string
    {
        return $this->lang->choice($id, $number, ...$args);
    }

    public function hasLang(string $id, ?string $locale = null, bool $fallback = true): bool
    {
        return $this->lang->has($id, $locale, $fallback);
    }

    /**
     * @return LangService
     */
    public function getLang(): LangService
    {
        return $this->lang;
    }
}
