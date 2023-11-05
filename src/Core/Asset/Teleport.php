<?php

declare(strict_types=1);

namespace Windwalker\Core\Asset;

use LogicException;
use Windwalker\Utilities\Options\OptionAccessTrait;

/**
 * The Teleport class.
 *
 * @since  3.0
 */
class Teleport
{
    use OptionAccessTrait;

    /**
     * Property templates.
     *
     * @var  array
     */
    protected array $stack = [];

    /**
     * Property currentName.
     *
     * @var  ?string
     */
    protected ?string $currentName = null;

    /**
     * AssetTemplate constructor.
     *
     * @param  array  $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * addTemplate
     *
     * @param  string  $name
     * @param  string  $string
     *
     * @return  static
     */
    public function add(string $name, string $string): static
    {
        $this->stack[$name] = $string;

        return $this;
    }

    /**
     * removeTemplate
     *
     * @param  string  $name
     *
     * @return  static
     */
    public function remove(string $name): static
    {
        if (isset($this->stack[$name])) {
            unset($this->stack[$name]);
        }

        return $this;
    }

    /**
     * resetTemplates
     *
     * @return  static
     */
    public function reset(): static
    {
        $this->stack = [];

        return $this;
    }

    /**
     * renderTemplate
     *
     * @return  string
     */
    public function render(): string
    {
        $html = '';

        if ($this->getOption('debug')) {
            $html .= "\n\n<!-- Start Teleport -->\n\n";
        }

        foreach ($this->stack as $name => $template) {
            if ($this->getOption('debug')) {
                $html .= sprintf("\n<!-- $name -->\n");
            }

            $html .= $template;
        }

        return $html;
    }

    /**
     * startTemplate
     *
     * @param  string  $__assetTemplateName
     * @param  array   $__assetTemplateData
     *
     * @return  $this
     */
    public function start(string $__assetTemplateName, array $__assetTemplateData = []): static
    {
        if ($this->currentName) {
            throw new LogicException(
                'Does not support nested teleport for: ' . $__assetTemplateName .
                '. current teleport is: ' . $this->currentName
            );
        }

        $this->currentName = $__assetTemplateName;

        extract($__assetTemplateData, EXTR_OVERWRITE);

        ob_start();

        return $this;
    }

    /**
     * endTemplate
     *
     * @return  static
     */
    public function end(): static
    {
        $content = ob_get_clean();

        $this->add($this->currentName, $content);

        $this->currentName = null;

        return $this;
    }
}
