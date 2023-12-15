{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Utilities\Enum\EnumTranslatableInterface;
use Windwalker\Utilities\Enum\EnumTranslatableTrait;
use Windwalker\Utilities\Contract\LanguageInterface;

enum {% pascal($name) %} implements EnumTranslatableInterface
{
    use EnumTranslatableTrait;

    // case CASE = '';

    public function trans(LanguageInterface $lang, ...$args): string
    {
        return $lang->trans('app.{% dot($name) %}.' . $this->getKey());
    }
}
