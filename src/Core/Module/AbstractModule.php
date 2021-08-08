<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Module;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\State\AppState;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

/**
 * The AbstractModule class.
 */
abstract class AbstractModule implements ModuleInterface
{
    protected ?string $name = null;

    #[Inject]
    protected AppContext $app;

    public function getState(): AppState
    {
        return $this->app->getState($this->getName());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name ??= $this->guessName();
    }

    /**
     * @param  string  $name
     *
     * @return  static  Return self to support chaining.
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function guessName(): string
    {
        $root = $this->app->config('asset.namespace_base');

        $ref = new \ReflectionClass($this);
        $ns = $ref->getNamespaceName();

        return StrNormalize::toDotSeparated(Str::removeLeft($ns, $root));
    }

    /**
     * @return AppContext
     */
    public function getAppContext(): AppContext
    {
        return $this->app;
    }
}
