<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Windwalker\Utilities\Options\RecordOptionsTrait;

use function Windwalker\get_object_props;
use function Windwalker\get_object_values;

class NavOptions implements NavConstantInterface, \JsonSerializable
{
    use RecordOptionsTrait {
        wrap as parentWrap;
    }

    public function __construct(
        public ?NavMode $mode = null,
        public ?bool $debugAlert = null,
        public ?bool $mute = null,
        public ?bool $escape = null,
        public ?bool $allowOutside = null,
        public ?bool $instant = null,
        public ?bool $withoutVars = null,
        public ?bool $withoutQuery = null,
        public ?bool $ignoreEvents = null,
        public array|bool|null $allowQuery = null,
        public ?array $extra = null,
    ) {
    }

    public static function wrap(mixed $values, bool $clone = false): static
    {
        if (is_int($values)) {
            if ($values & NavConstantInterface::TYPE_RAW) {
                $mode = NavMode::RAW;
            } elseif ($values & NavConstantInterface::TYPE_PATH) {
                $mode = NavMode::PATH;
            } elseif ($values & NavConstantInterface::TYPE_FULL) {
                $mode = NavMode::FULL;
            } else {
                $mode = null;
            }

            return new static(
                mode: $mode,
                debugAlert: $values & NavConstantInterface::DEBUG_ALERT ? true : null,
                mute: $values & NavConstantInterface::MODE_MUTE ? true : null,
                escape: $values & NavConstantInterface::MODE_ESCAPE ? true : null,
                allowOutside: $values & NavConstantInterface::REDIRECT_ALLOW_OUTSIDE ? true : null,
                instant: $values & NavConstantInterface::REDIRECT_INSTANT ? true : null,
                withoutVars: $values & NavConstantInterface::WITHOUT_VARS ? true : null,
                withoutQuery: $values & NavConstantInterface::WITHOUT_QUERY ? true : null,
                ignoreEvents: $values & NavConstantInterface::IGNORE_EVENTS ? true : null,
            );
        }

        return static::parentWrap($values, $clone);
    }

    public function addAllowQuery(string $name): void
    {
        if ($this->allowQuery === false || $this->allowQuery === null) {
            $this->allowQuery = [];
        }

        if (!in_array($name, $this->allowQuery, true)) {
            $this->allowQuery[] = $name;
        }
    }

    public function allowAllQuery(bool $allow = true): void
    {
        $this->allowQuery = $allow;
    }

    public function jsonSerialize(): array
    {
        return get_object_values($this);
    }
}
