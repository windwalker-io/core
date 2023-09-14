<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Windwalker\ORM\Cast\CastInterface;

use function Windwalker\chronos;

/**
 * The ChronosCast class.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ChronosCast implements CastInterface
{
    public function __construct(protected ?ChronosService $chronosService = null)
    {
    }

    public function hydrate(mixed $value): ?Chronos
    {
        if ($value === null) {
            return null;
        }

        $value = chronos($value);

        if ($this->chronosService) {
            $value = $this->chronosService->toServer($value);
        }

        return $value;
    }

    public function extract(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $format = $this->chronosService
                ? $this->chronosService->getSqlFormat()
                : Chronos::FORMAT_YMD_HIS;

            $value = $value->format($format);
        }

        return (string) $value;
    }
}
