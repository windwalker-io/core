<?php

namespace Windwalker\SystemPackage\Command;

use Windwalker\Console\Command\Command;
use Windwalker\SystemPackage\Command\System\{
    ClearCacheCommand,
    DownCommand,
    GenerateCommand,
    LangMergeCommand,
    MailTestCommand,
    ModeCommand,
    UpCommand
};

/**
 * Class BuildCommand
 *
 * @since 1.0
 */
class SystemCommand extends Command
{
    /**
     * An enabled flag.
     *
     * @var bool
     */
    public static $isEnabled = true;

    /**
     * Property name.
     *
     * @var  string
     */
    protected $name = 'system';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'System operation.';

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
        $this->addCommand(GenerateCommand::class);
        $this->addCommand(ClearCacheCommand::class);
        $this->addCommand(LangMergeCommand::class);
        $this->addCommand(MailTestCommand::class);
        $this->addCommand(UpCommand::class);
        $this->addCommand(DownCommand::class);
        $this->addCommand(ModeCommand::class);

        parent::init();
    }
}
