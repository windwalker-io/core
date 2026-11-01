<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
// use Windwalker\Filter\Rule\Alnum;
// use Windwalker\Filter\Rule\CastTo;
// use Windwalker\Filter\Rule\Clamp;
// use Windwalker\Filter\Rule\Cmd;
// use Windwalker\Filter\Rule\DefaultValue;
// use Windwalker\Filter\Rule\EmailAddress;
// use Windwalker\Filter\Rule\IPAddress;
// use Windwalker\Filter\Rule\IPV4;
// use Windwalker\Filter\Rule\IPV6;
// use Windwalker\Filter\Rule\Length;
// use Windwalker\Filter\Rule\Negative;
// use Windwalker\Filter\Rule\RawValue;
// use Windwalker\Filter\Rule\Regex;
// use Windwalker\Filter\Rule\UrlAddress;
// use Windwalker\Filter\Rule\Words;

/**
 * syntaxes:
 *  - abs
 *  - alnum
 *  - cmd
 *  - email
 *  - url
 *  - words
 *  - ip
 *  - ipv4
 *  - ipv6
 *  - neg
 *  - raw
 *  - range(min=int, max=int)
 *  - clamp(min=int, max=int)
 *  - length(max=int, [utf8])
 *  - regex(regex=string, type='match'|'replace')
 *  - required
 *  - default(value=mixed)
 *  - func(callback)
 *  - string([strict])
 *  - int([strict])
 *  - float([strict])
 *  - array([strict])
 *  - bool([strict])
 *  - object([strict])
 *
 // Todo: Implement this after php 8.6
 // * @method static \Closure alnum()
 // * @method static \Closure cmd()
 // * @method static \Closure email()
 // * @method static \Closure url()
 // * @method static \Closure words()
 // * @method static \Closure ip()
 // * @method static \Closure ipv4()
 // * @method static \Closure ipv6()
 // * @method static \Closure negative()
 // * @method static \Closure raw()
 // * @method static \Closure clamp(int $min, int $max)
 // * @method static \Closure length(int $max, bool $utf8 = false)
 // * @method static \Closure regex(string $regex, string $type = 'match')
 // * @method static \Closure required()
 // * @method static \Closure default(mixed $value)
 // * @method static \Closure toArray(bool $strict = false)
 // * @method static \Closure toObject(bool $strict = false)
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Filter implements ContainerAttributeInterface
{
    public array $commands = [];

    public function __construct(
        string|array|\Closure ...$commands
    ) {
        $this->commands = $commands;
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            $value = $handler();

            foreach ($this->commands as $command) {
                if ($command instanceof \Closure) {
                    $value = ($command)($value);
                } else {
                    $value = $handler->container
                        ->get(FilterService::class)
                        ->filter($value, $command);
                }
            }

            return $value;
        };
    }

    // public static function alnum(): \Closure
    // {
    //     return static::__callStatic(__FUNCTION__);
    // }

    // Todo: Implement this after php 8.6
    // public static function __callStatic(string $name, array $args = [])
    // {
    //     return match (strtolower($name)) {
    //         'alnum' => fn ($v) => new Alnum(...$args)->filter($v),
    //         'cmd' => fn ($v) => new Cmd(...$args)->filter($v),
    //         'email' => fn ($v) => new EmailAddress(...$args)->filter($v),
    //         'url' => fn ($v) => new UrlAddress(...$args)->filter($v),
    //         'words' => fn ($v) => new Words(...$args)->filter($v),
    //         'ip' => fn ($v) => new IPAddress(...$args)->filter($v),
    //         'ipv4' => fn ($v) => new IPV4(...$args)->filter($v),
    //         'ipv6' => fn ($v) => new IPV6(...$args)->filter($v),
    //         'negative' => fn ($v) => new Negative(...$args)->filter($v),
    //         'raw' => fn ($v) => new RawValue(...$args)->filter($v),
    //         'clamp' => fn ($v) => new Clamp(...$args)->filter($v),
    //         'length' => fn ($v) => new Length(...$args)->filter($v),
    //         'regex' => fn ($v) => new Regex(...$args)->filter($v),
    //         'required' => fn ($v) => new Required(...$args)->filter($v),
    //         'default' => fn ($v) => new DefaultValue(...$args)->filter($v),
    //         'toArray' => fn ($v) => new CastTo('array', ...$args)->filter($v),
    //         // 'toBool' => fn ($v) => new CastTo('bool', ...$args)->filter($v),
    //         'toObject' => fn ($v) => new CastTo('object', ...$args)->filter($v),
    //         default => throw new \BadMethodCallException("Filter method $name not found."),
    //     };
    // }
}
