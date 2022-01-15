<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator;

use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\FileGenerator;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Utilities\Str;

/**
 * The CodeGenerator class.
 */
class CodeGenerator
{
    /**
     * CodeGenerator constructor.
     */
    public function __construct(protected ApplicationInterface $app)
    {
        include_once __DIR__ . '/generator-helpers.php';
    }

    public function from(string $src): FileCollection
    {
        $src = Str::ensureRight($src, '.tpl');

        $collection = new FileCollection(Filesystem::glob($src));
        $collection->addEventDealer($this->app);

        return $collection;
    }

    public static function extractNamePath(string $name, string $delimiter = '\\'): array
    {
        $names = preg_split('/\/|\\|./', $name);

        $name = (string) array_pop($names);

        $names = implode($delimiter, $names);

        return [$names, $name];
    }

    public function getOrCreateFile(string $filepath): FileGenerator
    {
        if (is_file($filepath)) {
            return FileGenerator::fromArray(
                [
                    'body' => file_get_contents($filepath),
                ]
            );
        }

        $fg = new FileGenerator();

        return $fg;
    }
}
