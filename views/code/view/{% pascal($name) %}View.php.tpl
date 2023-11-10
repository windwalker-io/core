{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;

#[ViewModel(
    layout: '{% kebab($name) %}',
    js: '{% kebab($name) %}.js'
)]
class {% pascal($name) %}View implements ViewModelInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Prepare View.
     *
     * @param  AppContext  $app   The web app context.
     * @param  View        $view  The view object.
     *
     * @return  mixed
     */
    public function prepare(AppContext $app, View $view): array
    {
        return [];
    }
}
