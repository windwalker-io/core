<?php

/**
 * Part of datavideo project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Language;

use JetBrains\PhpStorm\Language;
use Windwalker\DI\Attributes\Inject;

/**
 * The TranslatorTrait class.
 */
trait TranslatorTrait
{
    #[Inject]
    protected LangService $translator;

    public function trans(string $id, ...$args): string
    {
        return $this->translator->trans($id, ...$args);
    }

    public function choice(string $id, int|float $number, ...$args): string
    {
        return $this->translator->choice($id, $number, ...$args);
    }

    public function has(string $id, ?string $locale = null, bool $fallback = true): bool
    {
        return $this->translator->has($id, $locale, $fallback);
    }
}
