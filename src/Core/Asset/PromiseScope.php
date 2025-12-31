<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use Windwalker\Utilities\Str;

use function Windwalker\value;

class PromiseScope
{
    public protected(set) array $elements = [];

    public function __construct(protected string $start, protected string $moduleVar = 'module')
    {
    }

    public function add(string|\Stringable|\Closure $element): static
    {
        $this->elements[] = $element;

        return $this;
    }

    public function render(): string
    {
        $output = $this->start;
        $moduleVar = $this->moduleVar;

        if ($this->elements !== []) {
            $output = Str::removeRight(rtrim($output), ';');
            $output .= ".then(async ($moduleVar) => {\n";

            foreach ($this->elements as $element) {
                if ($element instanceof \Closure) {
                    $element = $element($this->moduleVar);
                }

                $output .= $element;
            }

            $output .= "\n});";
        } else {
            Str::ensureRight($output, ';');
        }

        return $output;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
