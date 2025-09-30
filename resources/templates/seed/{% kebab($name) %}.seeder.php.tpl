{% $phpOpen %}

declare(strict_types=1);

namespace App\Seeder;

use Windwalker\Core\Seed\AbstractSeeder;
use Windwalker\Core\Seed\SeedClear;
use Windwalker\Core\Seed\SeedImport;

return new /** {% pascal($name) %} Seeder */ class extends AbstractSeeder {
    #[SeedImport]
    public function import(): void
    {
        $faker = $this->faker('en_US');

        //
    }

    #[SeedClear]
    public function clear(): void
    {
        //
    }
};
