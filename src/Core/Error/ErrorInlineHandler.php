<?php

declare(strict_types=1);

namespace Windwalker\Core\Error;

use Throwable;
use Windwalker\Core\Application\ApplicationInterface;

class ErrorInlineHandler implements ErrorHandlerInterface
{
    public function __construct(protected ApplicationInterface $app, protected ?int $errorLevel = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Throwable $e): void
    {
        $display = $this->app->isDebug();

        if ($this->errorLevel !== null) {
            $display = (bool) ($this->errorLevel & error_reporting());
        }

        if ($display) {
            echo $e->getMessage();
        }
    }
}
