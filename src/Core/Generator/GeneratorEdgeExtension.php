<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator;

use Windwalker\Edge\Extension\DirectivesExtensionInterface;

/**
 * The GeneratorEdgeExtension class.
 */
class GeneratorEdgeExtension implements DirectivesExtensionInterface
{
    /**
     * getDirectives
     *
     * @return  callable[]
     */
    public function getDirectives(): array
    {
        return [
            'kebab' => [$this, 'kebab'],
            'pascal' => [$this, 'pascal'],
            'camel' => [$this, 'camel'],
        ];
    }

    public function kebab(string $expression): string
    {
        return "<?php echo \Windwalker\Utilities\StrNormalize::toKebabCase$expression ?>";
    }

    public function pascal(string $expression): string
    {
        return "<?php echo \Windwalker\Utilities\StrNormalize::toPascalCase$expression ?>";
    }

    public function camel(string $expression): string
    {
        return "<?php echo \Windwalker\Utilities\StrNormalize::toPCamelCase$expression ?>";
    }

    /**
     * getName
     *
     * @return  string
     */
    public function getName(): string
    {
        return 'generator';
    }
}
