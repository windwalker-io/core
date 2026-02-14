<?php

declare(strict_types=1);

namespace Windwalker\Console;

use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext as SymfonyCompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput;

use function Windwalker\ds;

class CompletionContext
{
    public ?int $currentWordIndex {
        get => $this->originalContext->getWordIndex();
    }

    public string $currentWord {
        get => (string) $this->originalContext->getCurrentWord();
    }

    public string $rawCurrentWord {
        get => (string) $this->originalContext->getRawCurrentWord();
    }

    public array $words {
        get => (array) $this->originalContext->getWords();
    }

    public array $rawWords {
        get => (array) $this->originalContext->getRawWords();
    }

    public IOInterface $io {
        get => $this->io ??= $this->getIO();
    }

    public function __construct(
        readonly public CompletionType $type,
        readonly public string $name,
        readonly public Command $currentCommand,
        public protected(set) SymfonyCompletionContext $originalContext,
    ) {
    }

    public function isArgument(): bool
    {
        return $this->type === CompletionType::ARGUMENT;
    }

    public function isOption(): bool
    {
        return $this->type === CompletionType::OPTION;
    }

    public function getDefinition(): InputDefinition
    {
        return $this->currentCommand->getDefinition();
    }

    public function getInput(): CompletionInput
    {
        $words = $this->words;
        array_shift($words);

        $definition = $this->getDefinition();

        $input = CompletionInput::fromTokens($words, $this->currentWordIndex);
        $input->bind($definition);

        return $input;
    }

    public function getIO(): IOInterface
    {
        return new IO($this->getInput(), new ConsoleOutput(), $this->currentCommand);
    }

    public function getArgument(string $name): mixed
    {
        return $this->getIO()->getArgument($name);
    }

    public function getOption(string $name): mixed
    {
        return $this->getIO()->getOption($name);
    }
}
