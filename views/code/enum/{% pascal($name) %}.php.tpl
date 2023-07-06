{% $phpOpen %}

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Utilities\Enum\EnumSingleton;
use Windwalker\Utilities\Enum\EnumTranslatableInterface;
use Windwalker\Utilities\Enum\EnumTranslatableTrait;
use Windwalker\Utilities\Contract\LanguageInterface;

/**
 * The {% pascal($name) %} enum class.
 */
enum {% pascal($name) %} implements EnumTranslatableInterface
{
    use EnumTranslatableTrait;

    // case CASE = '';

    public function trans(LanguageInterface $lang, ...$args): string
    {
        return $lang->trans('app.{% dot($name) %}.' . $this->getKey());
    }
}
