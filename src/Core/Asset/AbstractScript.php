<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use Windwalker\Core\Attributes\Ref;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Classes\InstanceMarcoableTrait;

/**
 * The AbstractScript class.
 *
 * @method AssetService css(string $url, array $options = [], array $attrs = [])
 * @method AssetService js(string $url, array $options = [], array $attrs = [])
 * @method AssetService internalCSS(string $content)
 * @method AssetService internalJS(string $content)
 * @method AssetService internalModule(string $content)
 * @method bool|string has(string $uri, bool $strict = false)
 */
abstract class AbstractScript
{
    use InstanceMarcoableTrait;

    #[Inject]
    protected AssetService $asset;

    #[Ref('unicorn.modules.next')]
    public bool $next = false;

    protected array $inited = [];

    /**
     * inited
     *
     * @param  string  $name
     * @param  mixed   ...$data
     *
     * @return bool
     */
    public function inited(string $name, ...$data): bool
    {
        $id = $this->getInitedId(...$data);

        if (!isset($this->inited[$name][$id])) {
            $this->inited[$name][$id] = true;

            return false;
        }

        return true;
    }

    /**
     * available
     *
     * @param  mixed  ...$data
     *
     * @return  bool
     *
     * @since  3.5.19
     */
    protected function available(...$data): bool
    {
        $debug = debug_backtrace();
        $stack = $debug[1];

        $method = $stack['class'] . '::' . $stack['function'];

        return !$this->inited($method, ...$data);
    }

    /**
     * getInitedId
     *
     * @param  mixed  ...$data
     *
     * @return  string
     */
    public function getInitedId(...$data): string
    {
        return md5(serialize($data));
    }

    public static function getJSObject(...$data): string
    {
        $quote = array_pop($data);

        if (!is_bool($quote)) {
            $data[] = $quote;
            $quote = false;
        }

        if (count($data) > 1) {
            $result = [];

            foreach ($data as $array) {
                $result = static::mergeOptions($result, $array);
            }
        } else {
            $result = $data[0];
        }

        return AssetService::getJSObject($result, $quote);
    }

    /**
     * mergeOptions
     *
     * @param  array  $options1
     * @param  array  $options2
     * @param  bool   $recursive
     *
     * @return  array
     */
    public static function mergeOptions(array $options1, array $options2, $recursive = true): array
    {
        if (!$recursive) {
            return array_merge($options1, $options2);
        }

        return Arr::mergeRecursive($options1, $options2);
    }

    public function warn(string $message): static
    {
        $this->internalJS("console.warn('$message')");

        return $this;
    }

    public function warnIfDebug(string $message): static
    {
        if ($this->asset->isDebug()) {
            $this->warn($message);
        }

        return $this;
    }

    /**
     * Handle dynamic calls to the asset object.
     *
     * @param  string  $method  The method name.
     * @param  array   $args    The arguments of method call.
     *
     * @return  mixed
     */
    public function __call(string $method, array $args)
    {
        return $this->asset->$method(...$args);
    }
}
