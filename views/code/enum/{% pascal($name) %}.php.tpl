{% $phpOpen %}

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace {% $ns %};

use MyCLabs\Enum\Enum;
use Windwalker\Utilities\Enum\EnumSingleton;
use Windwalker\Utilities\Enum\EnumTranslatableInterface;
use Windwalker\Utilities\Enum\EnumTranslatableTrait;
use Windwalker\Utilities\Contract\LanguageInterface;

/**
 * The {% pascal($name) %} enum class.
 *
 * @options Add options here.
 */
class {% pascal($name) %} extends EnumSingleton implements EnumTranslatableInterface
{
    use EnumTranslatableTrait;

    // public const CASE = '';

    /**
     * Unable to directly new this object.
     *
     * @param  mixed  $value
     *
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    protected function __construct(mixed $value)
    {
        parent::__construct($value);
    }

    public function trans(LanguageInterface $lang, ...$args): string
    {
        return $lang->trans('app.{% dot($name) %}.' . $this->getKey());
    }
}
