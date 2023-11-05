<?php

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Migration\Migration;
use Windwalker\Environment\Environment;

/**
 * The CountingOutputTrait class.
 *
 * @since  3.4.6
 */
trait CountingOutputTrait
{
    use MessageOutputTrait;

    /**
     * Property count.
     *
     * @var  int
     */
    public int $count = 0;

    /**
     * outCounting
     *
     * @return  static
     */
    public function outCounting(): static
    {
        if ($this instanceof Migration && $this->count === 0) {
            $this->emitMessage('');
        }

        // @see  https://gist.github.com/asika32764/19956edcc5e893b2cbe3768e91590cf1
        if (Environment::isWindows()) {
            $loading = ['|', '/', '-', '\\'];
        } else {
            $loading = ['◐', '◓', '◑', '◒'];
        }

        $this->count++;

        $icon = $loading[$this->count % count($loading)];

        $this->emitMessage("\r  ({$this->count}) $icon ", false);

        return $this;
    }

    /**
     * resetCount
     *
     * @return  $this
     *
     * @since  3.4.6
     */
    public function resetCount(): static
    {
        $this->count = 0;

        return $this;
    }
}
