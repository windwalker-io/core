{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\Attributes\ViewPrepare;
use Windwalker\Core\View\View;

#[ViewModel(
    layout: '{% kebab($name) %}',
    js: '{% kebab($name) %}.js'
)]
class {% pascal($name) %}View
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        //
    }

    #[ViewPrepare]
    public function prepare(AppContext $app, View $view): array
    {
        return [];
    }
}
