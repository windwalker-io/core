<?php

declare(strict_types=1);

namespace Windwalker\Core\Console\Collision;

use NunoMaduro\Collision\Contracts\SolutionsRepository as SolutionsRepositoryInterface;
use Throwable;
use Windwalker\Database\Exception\DatabaseQueryException;

/**
 * The SolutionRepository class.
 */
class SolutionRepository implements SolutionsRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getFromThrowable(Throwable $throwable): array
    {
        if ($throwable instanceof DatabaseQueryException) {
            return [
                'SQL' => new class ($throwable) {
                    public function __construct(protected DatabaseQueryException $throwable)
                    {
                    }

                    public function getSolutionTitle(): string
                    {
                        return 'SQL';
                    }

                    public function getSolutionDescription(): string
                    {
                        return $this->throwable->getDebugSql();
                    }

                    public function getDocumentationLinks(): array
                    {
                        return [];
                    }
                }
            ];
        }

        return [];
    }
}
