<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Module;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\State\AppState;

/**
 * Interface ModuleInterface
 */
interface ModuleInterface
{
    public function getState(): AppState;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param  string  $name
     *
     * @return  static  Return self to support chaining.
     */
    public function setName(string $name): static;

    /**
     * @return AppContext
     */
    public function getAppContext(): AppContext;
}
