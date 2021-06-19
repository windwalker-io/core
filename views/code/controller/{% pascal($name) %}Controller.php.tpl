{% $phpOpen %}

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Core\Attributes\Controller;

/**
 * The {% pascal($name) %}Controller class.
 */
#[Controller(
    config: __DIR__ . '/{% kebab($name) %}.config.php'
)]
class {% pascal($name) %}Controller
{
    //
}
