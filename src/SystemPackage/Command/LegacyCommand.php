<?php

namespace Windwalker\SystemPackage\Command;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\SystemPackage\Command\Legacy\ConvertConfigCommand;
use Windwalker\SystemPackage\Command\Legacy\ConvertRoutingCommand;

/**
 * Class BuildCommand
 *
 * @since 1.0
 */
class LegacyCommand extends CoreCommand
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
    protected $name = 'legacy';

    /**
     * Property description.
     *
     * @var  string
     */
    protected $description = 'Some legacy conversions.';

    /**
     * init
     *
     * @return  void
     */
    protected function init()
    {
        $this->addCommand(ConvertRoutingCommand::class);
        $this->addCommand(ConvertConfigCommand::class);

        parent::init();
    }
}
