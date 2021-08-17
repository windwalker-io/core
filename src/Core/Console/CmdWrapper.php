<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use Symfony\Component\Process\Process;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Utilities\Wrapper\WrapperInterface;

/**
 * The CmdWrapper class.
 */
class CmdWrapper implements WrapperInterface
{
    /**
     * CmdWrapper constructor.
     *
     * @param  string|\Closure       $cmd
     * @param  string|null  $input
     * @param  bool         $ignoreError
     */
    public function __construct(
        public string|\Closure $cmd,
        public ?string $input = null,
        public bool $ignoreError = false
    ) {
    }

    public function toProcess(ApplicationInterface $app): Process
    {
        if ($this->cmd instanceof \Closure) {
            return $app->call($this->cmd);
        }

        return $app->createProcess($this->cmd)
            ->setInput($this->input);
    }

    /**
     * Get wrapped value.
     *
     * @param  mixed  $src
     *
     * @return  mixed
     *
     * @since  4.0
     */
    public function __invoke(mixed $src): Process
    {
        return $this->toProcess($src);
    }
}
