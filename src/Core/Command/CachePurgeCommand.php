<?php

declare(strict_types=1);

namespace Windwalker\Core\Command;

use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Cache\CachePool;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\CompletionContext;
use Windwalker\Console\CompletionHandlerInterface;
use Windwalker\Console\Input\InputOption;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\DI\Exception\DependencyResolutionException;

/**
 * The ClearCommand class.
 */
#[CommandWrapper(
    description: 'Clear cache by profiles.'
)]
class CachePurgeCommand implements CommandInterface, CompletionHandlerInterface
{
    public function __construct(protected ApplicationInterface $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'profiles',
            InputArgument::IS_ARRAY,
            'Clear these profiles, if not provided, will clear all profiles.'
        );

        $command->addOption(
            'group',
            'g',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Clear these groups, default is empty group.'
        );

        $command->addOption(
            'tag',
            't',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Invalidate tags, if not provided, will clear all tags.'
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        $profiles = $io->getArgument('profiles');

        if (!count($profiles)) {
            $profiles = $this->getCacheProfiles();
        }

        foreach ($profiles as $profile) {
            try {
                $this->clearCacheProfile($profile, $io);
            } catch (DependencyResolutionException) {
                $io->writeln(sprintf('<error>Profile "%s" not found.</error>', $profile));
            }
        }

        $io->writeln('Cache cleared.');

        return 0;
    }

    protected function clearCacheProfile(string $profile, IOInterface $io): void
    {
        $cache = $this->app->retrieve(CachePool::class, tag: $profile);

        $groups = (array) $io->getOption('group');
        $tags = (array) $io->getOption('tag');

        if ($groups === []) {
            $caches = ['__main__' => $cache];
            $hasGroups = false;
        } else {
            $caches = [];
            $hasGroups = true;

            foreach ($groups as $group) {
                if (!$cache->isGroupSupported()) {
                    continue;
                }

                $caches[$group] = $cache->withGroup($group);
            }
        }

        if ($caches !== []) {
            foreach ($caches as $group => $cache) {
                if ($tags === []) {
                    $cache->clear();

                    $io->writeln(
                        sprintf(
                            '[Clear] <info>%s%s</info>',
                            $profile,
                            $hasGroups ? ':' . $group : ''
                        )
                    );
                } else {
                    $cache->invalidateTags(...$tags);

                    $io->writeln(
                        sprintf(
                            '[Invalidate] <info>%s%s</info> - Tags: <comment>%s</comment>',
                            $profile,
                            $hasGroups ? ':' . $group : '',
                            implode(',', $tags)
                        )
                    );
                }
            }
        }

    }

    /**
     * @return  int[]|string[]
     */
    public function getCacheProfiles(): array
    {
        $instances = $this->app->config('cache.factories.instances') ?? [];

        return array_keys($instances);
    }

    public function handleCompletions(CompletionContext $context): ?array
    {
        if ($context->isArgument()) {
            if ($context->name === 'profiles') {
                return $this->getCacheProfiles();
            }
        }

        return null;
    }
}
