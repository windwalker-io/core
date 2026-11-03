<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Runtime;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\DI\Container;
use Windwalker\Utilities\Env;

/**
 * Runtime holds its state in static properties, some of which may already be
 * populated by the host application bootstrap before this test runs. Every
 * test backs up and restores that global state so it never leaks into other
 * test files.
 */
class RuntimeTest extends TestCase
{
    private array $serverBackup = [];

    private ?string $proxyTrustedIpsBackup = null;

    private ?string $appEnvBackup = null;

    private bool $envWasInitialized = false;

    private string $envBackup = '';

    private AppVerbosity $verbosityBackup;

    private string $rootDirBackup;

    private string $workDirBackup;

    private bool $bootedBackup;

    private ?Container $containerBackup;

    #[Before]
    public function backupRuntimeState(): void
    {
        $this->serverBackup = $_SERVER;
        $this->proxyTrustedIpsBackup = Env::get('PROXY_TRUSTED_IPS');
        $this->appEnvBackup = Env::get('APP_ENV');

        $envProp = new ReflectionProperty(Runtime::class, 'env');

        $this->envWasInitialized = $envProp->isInitialized();

        if ($this->envWasInitialized) {
            $this->envBackup = $envProp->getValue();
        }

        $this->verbosityBackup = Runtime::$verbosity;
        $this->rootDirBackup = Runtime::getRootDir();
        $this->workDirBackup = Runtime::getWorkDir();
        $this->bootedBackup = Runtime::isBooted();
        $this->containerBackup = $this->readStaticProperty('container');
    }

    #[After]
    public function restoreRuntimeState(): void
    {
        $_SERVER = $this->serverBackup;

        if ($this->proxyTrustedIpsBackup === null) {
            unset($_SERVER['PROXY_TRUSTED_IPS'], $_ENV['PROXY_TRUSTED_IPS']);
        } else {
            $_SERVER['PROXY_TRUSTED_IPS'] = $this->proxyTrustedIpsBackup;
        }

        if ($this->appEnvBackup === null) {
            unset($_SERVER['APP_ENV'], $_ENV['APP_ENV']);
        } else {
            $_SERVER['APP_ENV'] = $this->appEnvBackup;
        }

        if ($this->envWasInitialized) {
            Runtime::setEnv($this->envBackup);
        }

        Runtime::$verbosity = $this->verbosityBackup;
        $this->writeStaticProperty('rootDir', $this->rootDirBackup);
        $this->writeStaticProperty('workDir', $this->workDirBackup);
        $this->writeStaticProperty('booted', $this->bootedBackup);
        $this->writeStaticProperty('container', $this->containerBackup);
    }

    private function readStaticProperty(string $name): mixed
    {
        return new ReflectionProperty(Runtime::class, $name)->getValue();
    }

    private function writeStaticProperty(string $name, mixed $value): void
    {
        new ReflectionProperty(Runtime::class, $name)->setValue(null, $value);
    }

    #[Test]
    public function getContainer_lazilyCreatesContainerWithConfigParameters(): void
    {
        $this->writeStaticProperty('container', null);

        $container = Runtime::getContainer();

        self::assertInstanceOf(Container::class, $container);
        self::assertInstanceOf(Config::class, $container->getParameters());
        self::assertSame($container, Runtime::getContainer());
    }

    #[Test]
    public function getConfig_returnsContainerParameters(): void
    {
        $this->writeStaticProperty('container', null);

        $container = Runtime::getContainer();

        self::assertSame($container->getParameters(), Runtime::getConfig());
    }

    #[Test]
    public function config_readsDeepValueFromContainerParameters(): void
    {
        $this->writeStaticProperty('container', null);

        Runtime::getContainer()->getParameters()->setDeep('foo.bar', 'baz');

        self::assertSame('baz', Runtime::config('foo.bar'));
    }

    #[Test]
    public function loadConfig_mergesArrayIntoContainerParameters(): void
    {
        $this->writeStaticProperty('container', null);

        $result = Runtime::loadConfig(['app' => ['name' => 'runtime-test']]);

        self::assertSame('runtime-test', Runtime::config('app.name'));
        self::assertSame($result, Runtime::getContainer()->getParameters());
    }

    #[Test]
    public function boot_setsRootDirAndWorkDirAndMarksBooted(): void
    {
        $this->writeStaticProperty('booted', false);
        $this->writeStaticProperty('container', null);

        Runtime::boot('/tmp/runtime-test-root', '/tmp/runtime-test-work', false);

        self::assertTrue(Runtime::isBooted());
        self::assertSame('/tmp/runtime-test-root', Runtime::getRootDir());
        self::assertSame('/tmp/runtime-test-work', Runtime::getWorkDir());
    }

    #[Test]
    public function boot_isIdempotentAndKeepsFirstRootDirAndWorkDir(): void
    {
        $this->writeStaticProperty('booted', false);
        $this->writeStaticProperty('container', null);

        Runtime::boot('/tmp/first-root', '/tmp/first-work', false);
        Runtime::boot('/tmp/second-root', '/tmp/second-work', false);

        self::assertSame('/tmp/first-root', Runtime::getRootDir());
        self::assertSame('/tmp/first-work', Runtime::getWorkDir());
    }

    #[Test]
    public function boot_setsVerboseVerbosityWhenDebugIsExplicitlyTrue(): void
    {
        $this->writeStaticProperty('booted', false);
        $this->writeStaticProperty('container', null);
        Runtime::$verbosity = AppVerbosity::NORMAL;

        Runtime::boot('/tmp/runtime-test-root', '/tmp/runtime-test-work', true);

        self::assertSame(AppVerbosity::VERBOSE, Runtime::$verbosity);
    }

    #[Test]
    public function boot_keepsNormalVerbosityWhenDebugIsExplicitlyFalse(): void
    {
        $this->writeStaticProperty('booted', false);
        $this->writeStaticProperty('container', null);
        Runtime::$verbosity = AppVerbosity::NORMAL;

        Runtime::boot('/tmp/runtime-test-root', '/tmp/runtime-test-work', false);

        self::assertSame(AppVerbosity::NORMAL, Runtime::$verbosity);
    }

    #[Test]
    public function boot_infersVerboseVerbosityFromDevEnvironmentWhenDebugIsNull(): void
    {
        $this->writeStaticProperty('booted', false);
        $this->writeStaticProperty('container', null);
        Runtime::setEnv('dev');
        Runtime::$verbosity = AppVerbosity::NORMAL;

        Runtime::boot('/tmp/runtime-test-root', '/tmp/runtime-test-work');

        self::assertSame(AppVerbosity::VERBOSE, Runtime::$verbosity);
    }

    #[Test]
    public function boot_keepsNormalVerbosityForNonDevEnvironmentWhenDebugIsNull(): void
    {
        $this->writeStaticProperty('booted', false);
        $this->writeStaticProperty('container', null);
        Runtime::setEnv('prod');
        Runtime::$verbosity = AppVerbosity::NORMAL;

        Runtime::boot('/tmp/runtime-test-root', '/tmp/runtime-test-work');

        self::assertSame(AppVerbosity::NORMAL, Runtime::$verbosity);
    }

    #[Test]
    public function setEnvAndGetEnv_roundTrip(): void
    {
        Runtime::setEnv('staging');

        self::assertSame('staging', Runtime::getEnv());
    }

    #[Test]
    public function sapi_returnsCurrentPhpSapiName(): void
    {
        self::assertSame(PHP_SAPI, Runtime::sapi());
    }

    #[Test]
    public function isCli_matchesCurrentPhpSapi(): void
    {
        self::assertSame(PHP_SAPI === 'cli', Runtime::isCli());
    }

    #[Test]
    public function isVerbose_reflectsCurrentVerbosityLevel(): void
    {
        Runtime::$verbosity = AppVerbosity::NORMAL;
        self::assertFalse(Runtime::isVerbose());

        Runtime::$verbosity = AppVerbosity::VERBOSE;
        self::assertTrue(Runtime::isVerbose());
    }

    #[Test]
    public function shouldBlock_returnsFalseWhenForEnvIsNull(): void
    {
        self::assertFalse(Runtime::shouldBlock(null, 'all'));
    }

    #[Test]
    public function shouldBlock_returnsFalseWhenForEnvIsEmptyArray(): void
    {
        self::assertFalse(Runtime::shouldBlock([], 'all'));
    }

    #[Test]
    public function shouldBlock_returnsFalseWhenCurrentEnvNotInForEnv(): void
    {
        Runtime::setEnv('prod');

        self::assertFalse(Runtime::shouldBlock(['dev'], null));
    }

    #[Test]
    public function shouldBlock_returnsFalseWhenAllowIpsContainsAll(): void
    {
        Runtime::setEnv('dev');

        self::assertFalse(Runtime::shouldBlock('dev', 'all'));
    }

    #[Test]
    public function shouldBlock_returnsFalseWhenAllowIpsContainsRemoteAddrLiteral(): void
    {
        Runtime::setEnv('dev');

        self::assertFalse(Runtime::shouldBlock('dev', 'REMOTE_ADDR'));
    }

    #[Test]
    public function shouldBlock_returnsFalseWhenDirectRemoteAddrIsAllowed(): void
    {
        Runtime::setEnv('dev');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);

        self::assertFalse(Runtime::shouldBlock('dev', ['203.0.113.5']));
    }

    #[Test]
    public function shouldBlock_acceptsCommaSeparatedAllowIpsString(): void
    {
        Runtime::setEnv('dev');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);

        self::assertFalse(Runtime::shouldBlock('dev', '203.0.113.5, 198.51.100.9'));
    }

    #[Test]
    public function shouldBlock_returnsTrueWhenDirectRemoteAddrIsNotAllowed(): void
    {
        Runtime::setEnv('dev');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);

        self::assertTrue(Runtime::shouldBlock('dev', ['198.51.100.9']));
    }

    #[Test]
    public function shouldBlock_returnsTrueWhenForwardedChainIsNotTrusted(): void
    {
        Runtime::setEnv('dev');
        $_SERVER['REMOTE_ADDR'] = '9.9.9.9';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        $_SERVER['PROXY_TRUSTED_IPS'] = '';

        self::assertTrue(Runtime::shouldBlock('dev', ['1.2.3.4']));
    }

    #[Test]
    public function shouldBlock_usesForwardedClientIpWhenProxyChainIsTrusted(): void
    {
        Runtime::setEnv('dev');
        $_SERVER['REMOTE_ADDR'] = '9.9.9.9';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
        $_SERVER['PROXY_TRUSTED_IPS'] = '9.9.9.9';

        self::assertFalse(Runtime::shouldBlock('dev', ['1.2.3.4']));
        self::assertTrue(Runtime::shouldBlock('dev', ['198.51.100.9']));
    }

    #[Test]
    public function ipBlock_doesNothingWhenShouldBlockIsFalse(): void
    {
        // The blocking branch of ipBlock()/forbidden() calls header()+exit()
        // and is intentionally not unit-tested; see
        // .github/docs/integration-test-backlog.md (Core/Runtime).
        Runtime::setEnv('dev');
        $_SERVER['REMOTE_ADDR'] = '203.0.113.5';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);

        $this->expectNotToPerformAssertions();

        Runtime::ipBlock('dev', ['203.0.113.5']);
    }
}
