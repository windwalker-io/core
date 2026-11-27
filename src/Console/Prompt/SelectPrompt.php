<?php

declare(strict_types=1);

namespace Windwalker\Console\Prompt;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Arrow-key (↑/↓) single select menu rendered on a raw TTY, similar to laravel/prompts `select()`.
 *
 * Keys: ↑/↓ or k/j to move, Enter to confirm, Ctrl+C to abort.
 */
class SelectPrompt
{
    public function __construct(protected OutputInterface $output) {}

    /**
     * Run the menu and return the index of the selected label.
     *
     * @param  string    $question  The question text.
     * @param  string[]  $labels    Menu labels.
     * @param  int       $index     Initially highlighted index.
     *
     * @return  int  The selected index.
     *
     * @throws  RuntimeException  When the user cancels with Ctrl+C.
     */
    public function run(string $question, array $labels, int $index = 0): int
    {
        $count = count($labels);

        $sttyState = trim((string) shell_exec('stty -g 2>/dev/null'));
        // -isig: deliver Ctrl+C as "\x03" instead of SIGINT, so the terminal is always restored.
        shell_exec('stty -icanon -echo -isig 2>/dev/null');

        $lines = 0;
        $render = function () use ($question, $labels, &$index, &$lines): void {
            if ($lines > 0) {
                // Move cursor up and clear the previously rendered menu.
                $this->output->write(sprintf("\e[%dA\e[0J", $lines), false, OutputInterface::OUTPUT_RAW);
            }

            $buffer = [
                "<question>$question</question>",
            ];

            foreach ($labels as $i => $label) {
                $buffer[] = $i === $index
                    ? "  <info>❯ $label</info>"
                    : "    $label";
            }

            $this->output->write(implode(PHP_EOL, $buffer) . PHP_EOL);

            $lines = count($buffer);
        };

        try {
            $render();

            while (($char = fgetc(STDIN)) !== false) {
                if ($char === "\e") {
                    $next = fgetc(STDIN);

                    if ($next === '[' || $next === 'O') {
                        $code = fgetc(STDIN);

                        if ($code === 'A') {
                            $index = ($index - 1 + $count) % $count;
                            $render();
                        } elseif ($code === 'B') {
                            $index = ($index + 1) % $count;
                            $render();
                        }
                    }

                    continue;
                }

                if ($char === 'k') {
                    $index = ($index - 1 + $count) % $count;
                    $render();
                    continue;
                }

                if ($char === 'j') {
                    $index = ($index + 1) % $count;
                    $render();
                    continue;
                }

                if ($char === "\n" || $char === "\r") {
                    break;
                }

                if ($char === "\x03") {
                    throw new RuntimeException('Selection cancelled.');
                }
            }
        } finally {
            if ($sttyState !== '') {
                shell_exec('stty ' . escapeshellarg($sttyState) . ' 2>/dev/null');
            } else {
                shell_exec('stty icanon echo 2>/dev/null');
            }
        }

        $this->output->writeln('');

        return $index;
    }
}
