<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console;

use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The CoreCommand class.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_FUNCTION | \Attribute::TARGET_METHOD)]
class CoreCommand extends Command implements ContainerAttributeInterface
{
    public ?IOInterface $io = null;

    protected mixed $handler = null;

    public function __construct(
        string $name,
        ?string $description = null,
        array $aliases = [],
        bool $hidden = false,
    ) {
        parent::__construct($name);

        if ($description !== null) {
            $this->setDescription($description);
        }

        $this->setAliases($aliases);
        $this->setHidden($hidden);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new IO($input, $output, $this);

        if ($this->handler instanceof CommandWrapperInterface) {
            $result = $this->handler->execute($io);
        } else {
            $result = 0;
        }

        if (is_bool($result)) {
            return $result ? 0 : 255;
        }

        return $result ?? 0;
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function (...$args) use ($handler) {
            $this->handler = $handler(...$args);

            $this->handler->configure($this);

            return $this;
        };
    }
}
