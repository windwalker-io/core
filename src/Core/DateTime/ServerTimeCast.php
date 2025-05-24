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

    public function __construct(
        public string|\DateTimeZone|bool $toServer = true,
        public string|\DateTimeZone|bool $toLocal = false,
        protected ?ChronosService $chronosService = null,
    ) {
        //
    }

    public function hydrate(mixed $value): ?Chronos
    {
        if ($value === null) {
            return null;
        }

        $value = chronos($value);

        $local = $this->toLocal;

        if ($local === true) {
            if (!$this->chronosService) {
                throw new \LogicException(
                    static::class . ' must put in Cast() attribute when toLocal is true, ' .
                    'otherwise please provide a static timezone.'
                );
            }

            $local = $this->chronosService->getTimezone();
        }

        if ($local) {
            $value = $value->setTimezone($local);
        }

        return $value;
    }

    public function extract(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $value = chronos($value);
            $server = $this->toServer;

            if ($server === true) {
                if (!$this->chronosService) {
                    throw new \LogicException(
                        static::class . ' must put in Cast() attribute when toServer is true, ' .
                        'otherwise please provide a static timezone.'
                    );
                }

                $server = $this->chronosService->getServerTimezone();
            }

            if ($server) {
                $value = $value->setTimezone($server);
            }

            $format = $this->chronosService?->getSqlFormat() ?? Chronos::FORMAT_YMD_HIS;

            $value = $value->format($format);
        }

        return (string) $value;
    }

    public function serialize(mixed $data): mixed
    {
        return $data;
    }
}
