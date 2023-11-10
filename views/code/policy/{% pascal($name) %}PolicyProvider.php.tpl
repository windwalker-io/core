{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Authorization\AuthorizationInterface;
use Windwalker\Authorization\PolicyProviderInterface;

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
