<?php

declare(strict_types=1);

namespace Windwalker\Core\Seed;

use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Migration\AbstractMigration;
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

    public int $count = 0;

    /**
     * @return  $this
     *
     * @deprecated  Use printCounting() instead.
     */
    public function outCounting(int $count = 1): static
    {
        return $this->printCounting($count);
    }

    public function printCounting(int $count = 1, string $message = ''): static
    {
        if ($this instanceof AbstractMigration && $this->count === 0) {
            $this->emitMessage('');
        }

        // @see  https://gist.github.com/asika32764/19956edcc5e893b2cbe3768e91590cf1
        if (Environment::isWindows()) {
            $loading = ['|', '/', '-', '\\'];
        } else {
            $loading = ['◐', '◓', '◑', '◒'];
        }

        $this->count += $count;

        $icon = $loading[$this->count % count($loading)];

        $this->emitMessage("\r  ({$this->count}) $icon $message", false);

        return $this;
    }

    public function resetCount(): static
    {
        $this->count = 0;

        return $this;
    }
}
