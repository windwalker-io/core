<?php

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Utilities\Str;

/**
 * Trait CommandPackageResolveTrait
 */
trait CommandPackageResolveTrait
{
    #[Inject]
    protected PackageRegistry $packageRegistry;

    public function configurePackageOptions(Command $command, ?string $description = null): void
    {
        $command->addOption(
            'pkg',
            'p',
            InputOption::VALUE_REQUIRED,
            $description ?? 'The package name.'
        );
    }

    public function getPackage(IOInterface|Input $io): ?AbstractPackage
    {
        $pkg = $io->getOption('pkg');

        if (!$pkg) {
            return null;
        }

        return $this->packageRegistry->getPackage($pkg);
    }

    public function getPackageNamespace(IOInterface|Input $io, string $suffix = ''): ?string
    {
        $package = $this->getPackage($io);

        if (!$package) {
            return null;
        }

        $ns = $package::namespace();

        if ($suffix) {
            $ns .= '\\' . $suffix;
        }

        return Str::ensureRight($ns, '\\');
    }

    public function getPackageDir(IOInterface $io, string $suffix = ''): ?string
    {
        $package = $this->getPackage($io);

        if (!$package) {
            return null;
        }

        $dir = $package::dir();

        if ($suffix) {
            $dir .= DIRECTORY_SEPARATOR . $suffix;
        }

        return Str::removeRight($dir, DIRECTORY_SEPARATOR);
    }
}
