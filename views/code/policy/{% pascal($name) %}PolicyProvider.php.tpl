{% $phpOpen %}

/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Authorization\AuthorizationInterface;
use Windwalker\Authorization\PolicyProviderInterface;

/**
 * The {% pascal($name) %}PolicyProvider class.
 */
class {% pascal($name) %}PolicyProvider implements PolicyProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(AuthorizationInterface $auth): void
    {
        //
    }
}
