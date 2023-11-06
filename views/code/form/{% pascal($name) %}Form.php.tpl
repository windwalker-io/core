{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Form\Field\HiddenField;
use Windwalker\Form\FieldDefinitionInterface;
use Windwalker\Form\Form;

class {% pascal($name) %}Form implements FieldDefinitionInterface
{
    /**
     * Define the form fields.
     *
     * @param  Form  $form  The Windwalker form object.
     *
     * @return  void
     */
    public function define(Form $form): void
    {
        $form->fieldset(
            'basic',
            function (Form $form) {
                $form->add('id', HiddenField::class);
            }
        );
    }
}
