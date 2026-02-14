<?php

declare(strict_types=1);

namespace Windwalker\Console;

interface CompletionHandlerInterface
{
    public function handleCompletions(CompletionContext $context): ?array;
}
