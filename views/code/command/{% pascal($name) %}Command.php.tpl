{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;

#[CommandWrapper(
    description: '{% $desc %}'
)]
class {% pascal($name) %}Command implements CommandInterface
{
    public function configure(Command $command): void
    {
        //
    }

    public function execute(IOInterface $io): int
    {
        return 0;
    }
}
