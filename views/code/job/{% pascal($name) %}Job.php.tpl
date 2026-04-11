{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Queue\Attributes\JobEntry;
use Windwalker\Queue\Attributes\JobFailed;
use Windwalker\Queue\Job\JobController;

class {% pascal($name) %}Job
{
    #[JobEntry]
    public function run(JobController $controller): void
    {
        //
    }

    // #[JobFailed]
    // public function failed(): void
    // {
    //     //
    // }
}
