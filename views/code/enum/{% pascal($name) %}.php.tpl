{% $phpOpen %}

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace App\Enum;

use MyCLabs\Enum\Enum;
use Windwalker\Form\Enum\EnumTranslatableInterface;
use Windwalker\Form\Enum\EnumTranslatableTrait;
use Windwalker\Language\Language;

/**
 * The {% pascal($name) %} enum class.
 *
 * @options Add options here.
 */
class {% pascal($name) %} extends Enum implements EnumTranslatableInterface
{
    use EnumTranslatableTrait;

    // public const OPTION = '';

    /**
     * Creates a new value of some type
     *
     * @psalm-pure
     *
     * @param  mixed  $value
     *
     * @psalm-param T $value
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    public function __construct(mixed $value)
    {
        parent::__construct($value);
    }

    public function trans(Language $lang, ...$args): string
    {
        return $lang->trans('app.{% dot($name) %}.' . $this->getValue());
    }
}
