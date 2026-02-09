{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\DOM\HTMLElement;
use Windwalker\Form\Field\AbstractField;

class {% pascal($name) %}Field extends AbstractField
{
    /**
     * @param  HTMLElement  $input
     *
     * @return  HTMLElement
     */
    public function prepareInput(HTMLElement $input): HTMLElement
    {
        return parent::prepareInput($input);
    }

    /**
     * @return  array
     */
    protected function getAccessors(): array
    {
        return array_merge(
            parent::getAccessors(),
            []
        );
    }
}
