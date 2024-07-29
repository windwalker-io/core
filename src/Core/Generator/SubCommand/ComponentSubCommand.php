<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Utilities\StrNormalize;

/**
 * The ComponentSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Edge component.'
)]
class ComponentSubCommand extends AbstractGeneratorSubCommand
{
    protected string $defaultNamespace = 'Component';

    protected string $defaultDir = 'Component';

    protected bool $requireDest = false;

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        parent::configure($command);

        $command->addOption(
            'options',
            'o',
            InputOption::VALUE_OPTIONAL
        );

        $command->addOption(
            'tmpl',
            't',
            InputOption::VALUE_NEGATABLE,
            'Should or n not add component template file.'
        );
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
        [, $name] = $this->getNameParts($io);
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No component name');

            return 255;
        }

        $tmplDest = $this->getRawDestPath($io);
        $tmpl = '';

        $addTmpl = $io->getOption('tmpl') ?? $io->askConfirmation('Add template file? [Y/n]');

        if ($addTmpl) {
            $tmpl = StrNormalize::toKebabCase($tmplDest) . '.' . StrNormalize::toKebabCase($name);
        }

        $this->codeGenerator->from($this->getViewPath('component/*Component.php.tpl'))
            ->replaceTo(
                $this->getDestPath($io),
                [
                    'tmpl' => $tmpl,
                    'name' => $name,
                    'ns' => $this->getNamespace($io),
                ],
                $force
            );

        if ($addTmpl) {
            $tmplDest = $this->getRootDir($io) . '/views/components/' . StrNormalize::toKebabCase($tmplDest);

            $this->codeGenerator->from($this->getViewPath('component/*.blade.php.tpl'))
                ->replaceTo(
                    $tmplDest,
                    [
                        'name' => $name,
                        'ns' => $this->getNamespace($io),
                    ],
                    $force
                );
        }

        return 0;
    }
}
