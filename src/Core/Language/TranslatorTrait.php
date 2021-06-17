<?php

/**
 * Part of datavideo project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Language;

use Windwalker\DI\Attributes\Inject;

/**
 * The TranslatorTrait class.
 */
trait TranslatorTrait
{
    #[Inject]
    protected LangService $langService;

    public function trans(string $id, ...$args): string
    {
        return $this->langService->trans($id, ...$args);
    }

    public function choice(string $id, int|float $number, ...$args): string
    {
        return $this->langService->choice($id, $number, ...$args);
    }

    public function has(string $id, ?string $locale = null, bool $fallback = true): bool
    {
        return $this->langService->has($id, $locale, $fallback);
    }
}
