{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\DOM\DOMElement;
use Windwalker\Form\Field\AbstractField;

class {% pascal($name) %}Field extends AbstractField
{
    /**
     * @param  DOMElement  $input
     *
     * @return  DOMElement
     */
    public function prepareInput(DOMElement $input): DOMElement
    {
        return $input;
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
