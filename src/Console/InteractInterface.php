<?php

declare(strict_types=1);

namespace Windwalker\Console;

/**
 * The Interactinterface class.
 */
interface InteractInterface
{
    /**
     * Interaction with user.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     */
    public function interact(IOInterface $io): void;
}
