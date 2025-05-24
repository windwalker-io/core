<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Windwalker\ORM\Cast\CompositeCastInterface;
use Windwalker\ORM\Cast\CompositeCastTrait;

use function Windwalker\chronos;

/**
 * The ChronosCast class.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ServerTimeCast implements CompositeCastInterface
{
    use CompositeCastTrait;

    public const int HYDRATE_TO_LOCAL = 1 << 3;
    public const int EXTRACT_TO_SERVER = 1 << 4;

    public function __construct(
        protected ?ChronosService $chronosService = null,
        public int $options = self::EXTRACT_TO_SERVER
    ) {
        //
    }

    public function hydrate(mixed $value): ?Chronos
    {
        if ($value === null) {
            return null;
        }

        $value = chronos($value);

        if (
            $this->chronosService
            && ($this->options & static::HYDRATE_TO_LOCAL)
        ) {
            $value = $this->chronosService->toLocal($value);
        }

        return $value;
    }

    public function extract(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            if ($this->chronosService) {
                if ($this->options & static::EXTRACT_TO_SERVER) {
                    $value = $this->chronosService->toServer($value, $value->getTimezone());
                }

                $format = $this->chronosService->getSqlFormat();
            } else {
                $format = Chronos::FORMAT_YMD_HIS;
            }

            $value = $value->format($format);
        }

        return (string) $value;
    }

    public function serialize(mixed $data): mixed
    {
        return $data;
    }
}
