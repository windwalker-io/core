<?php

declare(strict_types=1);

namespace Windwalker\Core\Command;

use RuntimeException;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\Input\InputOption;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Folder;
use Windwalker\Filesystem\Path;

use function Windwalker\ds;
use function Windwalker\fs;

/**
 * The LangMergeCommand class.
 */
#[CommandWrapper(
    description: 'Merge language files.'
)]
class LangMergeCommand implements CommandInterface, CompletionAwareInterface
{
    use CommandPackageResolveTrait;

    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'File name to merge.'
        );

        $command->addArgument(
            'locales',
            InputArgument::IS_ARRAY,
            'The locales and direction, can be "{to} {from}", "{from} \'>\' {to}" or "{to} \'<\' {from}".',
            // $this->app->config('language.locale') ?? 'en-US'
        );

        // $command->addArgument(
        //     'locale2',
        //     InputArgument::OPTIONAL,
        //     'The locale to copy from.',
        //     $this->app->config('language.fallback') ?? 'en-US'
        // );

        $command->addOption(
            'dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Base language dir.'
        );

        $command->addOption(
            'replace',
            'r',
            InputOption::VALUE_NONE,
            'Replace current file instead save to tmp.'
        );

        $command->addOption(
            'sort',
            's',
            InputOption::VALUE_NONE,
            'Sort language keys.'
        );

        $command->addOption(
            'clear',
            'c',
            InputOption::VALUE_NEGATABLE,
            'Clear unexists lang keys or not.'
        );

        $this->configurePackageOptions($command);
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $filename = $io->getArgument('filename');
        $locales = $io->getArgument('locales');
        $clear = $io->getOption('clear');

        [$to, $from] = $this->getToAndFrom($locales);

        $dir = $io->getOption('dir')
            ?: $this->getPackageDir($io, '../resources/languages')
            ?? $this->app->path('@languages');
        $sort = $io->getOption('sort');
        $replace = $io->getOption('replace');

        $dir = Path::realpath($dir);

        if (str_contains($filename, '*')) {
            $files = Filesystem::glob($dir . '/' . $from . '/**/*');
        } else {
            $files = [new FileObject($dir . '/' . $from . '/' . $filename)];
        }

        foreach ($files as $file) {
            $fromFile = $file;
            $toFile = fs($dir . '/' . $to . '/' . $file->getBasename());

            if (!$fromFile?->exists()) {
                throw new RuntimeException('File: ' . $fromFile . ' not exists.');
            }

            $data = $fromFile->readAndParse()->collapse();

            if ($toFile->exists()) {
                $targetData = $toFile->readAndParse()
                    ->collapse();

                if ($clear) {
                    $targetData = $targetData->only($data->keys()->dump());
                }

                $data = $data->load($targetData);
            }

            if ($sort) {
                $data = $data->sortKeys();
            }

            $dest = $replace ? $toFile : fs(WINDWALKER_TEMP . '/language/' . $to . '/' . $filename);

            $dest->write($data->toString($toFile->getExtension()));

            $io->writeln(sprintf('File handled: <info>%s</info>', $dest));
        }

        if (!$replace) {
            $io->newLine();
            $io->writeln('(You can use -r|--replace to just override language file instead save to tmp.)');
        }

        return 0;
    }

    protected function getToAndFrom(array $locales): array
    {
        $current = $this->app->config('language.locale') ?? 'en-US';
        $fallback = $this->app->config('language.fallback') ?? 'en-US';

        if (count($locales) === 0) {
            return [$current, $fallback];
        }

        if (count($locales) === 1) {
            $locales[] = $fallback;

            return $locales;
        }

        if (count($locales) === 2) {
            return $locales;
        }

        if (count($locales) === 3) {
            $dir = match ($locales[1]) {
                '>' => 2,
                '<' => 0,
                default => throw new RuntimeException('Direction must be \'>\' or \'<\''),
            };

            $to = $locales[$dir];
            $from = $locales[$dir ? 0 : 2];

            return [$to, $from];
        }

        throw new RuntimeException('Too many locales given.');
    }

    public function completeOptionValues($optionName, CompletionContext $context)
    {
    }

    public function completeArgumentValues($argumentName, CompletionContext $context)
    {
        // if ($argumentName === 'filename') {
        //
        //
        //     $files = Filesystem::files(WINDWALKER_RESOURCES . '/languages');
        //
        //     return array_map(fn(FileObject $file) => $file->getBasename(), iterator_to_array($files));
        // }
    }
}
