<?php

declare(strict_types=1);

namespace Windwalker\Core\Http\Exception;

use Windwalker\Utilities\Attributes\AttributesAccessor;
use Windwalker\Utilities\Attributes\Enum\Title;

use function Windwalker\ds;

/**
 * The ApiException class.
 */
class ApiException extends \RuntimeException
{
    protected int $statusCode;

    protected string $errCode = '';

    public array $data = [];

    public array $debugData = [];

    public function __construct(
        string $message = '',
        int|string $code = 0,
        int $statusCode = 0,
        ?\Throwable $previous = null
    ) {
        $this->errCode = (string) $code;

        if (is_string($code)) {
            $code = 500;
        }

        parent::__construct($message, $code, $previous);

        $statusCode = $statusCode ?: static::getStatusCodeFromErrorCode($code);

        $this->setStatusCode($statusCode);
    }

    public static function from(\Throwable $e, int $statusCode = 0): static
    {
        return new static($e->getMessage(), $e->getCode(), $statusCode, $e);
    }

    public static function fromEnum(\UnitEnum $enum, ?string $message = null, ?\Throwable $previous = null): static
    {
        $message = $message ?: static::getAttrFromEnum($enum, Title::class)?->string;

        [$code, $statusCode] = static::getStatusCodeFromEnum($enum);

        return new static($message, $code, $statusCode, $previous);
    }

    public static function wrap(\Throwable $e): static
    {
        if ($e instanceof self) {
            return $e;
        }

        return static::from($e);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param  int|\UnitEnum  $statusCode
     *
     * @return  static  Return self to support chaining.
     * @throws \ReflectionException
     */
    public function setStatusCode(int|\UnitEnum $statusCode): static
    {
        if ($statusCode instanceof \UnitEnum) {
            [, $statusCode] = static::getStatusCodeFromEnum($statusCode);
        }

        $this->statusCode = $statusCode;

        return $this;
    }

    public static function getStatusCodeFromErrorCode(int $code): int
    {
        $str = (string) $code;

        $status = (int) substr($str, 0, 3);

        if ($status >= 200 && $status < 400) {
            return $code;
        }

        return $status;
    }

    /**
     * @template T
     *
     * @param  \UnitEnum               $enum
     * @param  string|class-string<T>  $attr
     *
     * @return  T|object|null
     *
     * @throws \ReflectionException
     */
    protected static function getAttrFromEnum(\UnitEnum $enum, string $attr): ?object
    {
        $ref = new \ReflectionEnum($enum);

        if (!$ref->hasCase($enum->name)) {
            return null;
        }

        $case = $ref->getCase($enum->name);

        return AttributesAccessor::getFirstAttributeInstance($case, $attr);
    }

    /**
     * @param  \UnitEnum  $statusCode
     *
     * @return  void
     */
    protected static function checkEnumBacked(\UnitEnum $statusCode): void
    {
        if (!$statusCode instanceof \BackedEnum) {
            throw new \LogicException('Must set `HttpStatus(code)` attribute if use UnitEnum as error code.');
        }
    }

    /**
     * @param  \UnitEnum  $enum
     *
     * @return  array{ 0: int|string, 1: int }
     *
     * @throws \ReflectionException
     */
    protected static function getStatusCodeFromEnum(\UnitEnum $enum): array
    {
        $status = static::getAttrFromEnum($enum, HttpStatus::class);

        if ($status) {
            $statusCode = $status->status ?: 0;

            $code = $enum instanceof \BackedEnum ? $enum->value : $statusCode;
        } else {
            static::checkEnumBacked($enum);

            $code = $enum->value;
            $statusCode = 500;
        }

        return [$code, $statusCode];
    }

    public function getErrCode(): string|int
    {
        return $this->errCode ?: $this->code;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function details(...$args): static
    {
        $this->data = [...$this->data, ...$args];

        return $this;
    }

    public function setDebugData(array $debugData): static
    {
        $this->debugData = $debugData;

        return $this;
    }

    public function debugDetails(...$args): static
    {
        $this->debugData = [...$this->debugData, ...$args];

        return $this;
    }
}
