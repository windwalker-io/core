{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Form\Attributes\Fieldset;
use Windwalker\Form\Field\HiddenField;
use Windwalker\Form\Attributes\FormDefine;
use Windwalker\Form\Form;

class {% pascal($name) %}Form
{
    #[FormDefine]
    #[Fieldset('basic')]
    public function basic(Form $form): void
    {
        $form->add('id', HiddenField::class);
    }
}
