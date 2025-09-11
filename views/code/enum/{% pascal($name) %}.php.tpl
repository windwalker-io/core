{% $phpOpen %}

declare(strict_types=1);

namespace {% $ns %};

use Windwalker\Utilities\Enum\EnumRichInterface;
use Windwalker\Utilities\Enum\EnumRichTrait;

enum {% pascal($name) %}{% $type %} implements EnumRichInterface
{
    use EnumRichTrait;

<?php if ($cases): ?>
<?php foreach ($cases as $name => $value): ?>
    case <?= $name ?> = '<?= $value ?>';
<?php endforeach; ?>
<?php else: ?>
    // case CASE = '';
<?php endif; ?>

    protected function translateKey(string $name): string
    {
        return "app.enum.{% dot($name) %}.$name";
    }
}
