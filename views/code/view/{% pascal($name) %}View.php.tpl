{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewMetadata;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\Attributes\ViewPrepare;
use Windwalker\Core\Html\HtmlFrame;
use Windwalker\Core\View\View;

#[ViewModel(
    layout: '{% kebab($name) %}',
    js: '{% kebab($name) %}.ts'
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

    #[ViewMetadata]
    protected function prepareMetadata(HtmlFrame $htmlFrame): void
    {
        //
    }
}
