<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Runtime;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Runtime\ConfigLoader;
use Windwalker\Utilities\Env;

class ConfigLoaderTest extends TestCase
{
    private ?string $appEnvBackup = null;

    #[Before]
    public function backupEnv(): void
    {
        $this->appEnvBackup = Env::get('APP_ENV');
    }

    #[After]
    public function restoreEnv(): void
    {
        if ($this->appEnvBackup === null) {
            unset($_SERVER['APP_ENV'], $_ENV['APP_ENV']);
        } else {
            $_SERVER['APP_ENV'] = $this->appEnvBackup;
        }
    }

    #[Test]
    public function includeArrays_mergesPlainArrayFileAsNamedModule(): void
    {
        $result = ConfigLoader::includeArrays(__DIR__ . '/fixtures/plain/*.php');

        self::assertSame(
            ['plain' => ['from' => 'plain-array']],
            $result
        );
    }

    #[Test]
    public function includeArrays_mergesEachKeyOfListedArrayItemsAsSeparateModule(): void
    {
        $result = ConfigLoader::includeArrays(__DIR__ . '/fixtures/multi/*.php');

        self::assertSame(
            [
                'listed-a' => ['from' => 'multi-array-a'],
                'listed-b' => ['from' => 'multi-array-b'],
            ],
            $result
        );
    }

    #[Test]
    public function includeArrays_excludesDisabledAndEnvMismatchedClosureModules(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $result = ConfigLoader::includeArrays(__DIR__ . '/fixtures/gating/*.php');

        self::assertSame(
            ['closure-named' => ['from' => 'closure-named']],
            $result
        );
    }

    #[Test]
    public function includeArrays_includesClosureModuleWhenEnvMatches(): void
    {
        $_SERVER['APP_ENV'] = 'staging';

        $result = ConfigLoader::includeArrays(__DIR__ . '/fixtures/gating/*.php');

        // Processing order follows module priority, not file order, so keys
        // may come back in a different order; only the key/value pairs matter.
        self::assertEqualsCanonicalizing(
            [
                'closure-named' => ['from' => 'closure-named'],
                'closure-env-mismatch' => ['from' => 'closure-env-mismatch'],
            ],
            $result
        );
    }

    #[Test]
    public function includeArrays_mergesUnnamedClosureModulesWithHigherPriorityWinningOnConflict(): void
    {
        $result = ConfigLoader::includeArrays(__DIR__ . '/fixtures/priority/*.php');

        self::assertEqualsCanonicalizing(
            [
                'shared' => 'from-high',
                'only-low' => 'low-val',
                'only-high' => 'high-val',
            ],
            $result
        );
    }

    #[Test]
    public function getFileName_stripsPhpExtensionFromPath(): void
    {
        self::assertSame(
            'plain-array.config',
            ConfigLoader::getFileName('/some/path/plain-array.config.php')
        );
    }
}
