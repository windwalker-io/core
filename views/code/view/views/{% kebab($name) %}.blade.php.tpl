{% $phpOpen %}

declare(strict_types=1);

namespace App\View;

/**
 * Global variables
 * --------------------------------------------------------------
 * @var $app       AppContext      Application context.
 * @var  $vm        {% pascal($name) %}View  The view model object.
 * @var $uri       SystemUri       System Uri information.
 * @var $chronos   ChronosService  The chronos datetime service.
 * @var $nav       Navigator       Navigator object to build route.
 * @var $asset     AssetService    The Asset manage service.
 * @var $lang      LangService     The language translation service.
 */

use {% $ns %}\{% pascal($name) %}View;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

{% $phpClose %}

@push('macro')
<style data-macro type="text/scss" data-scope=".view-{% kebab($name) %}">
</style>

<script data-macro="{% dot($name) %}" lang="ts" type="module">
</script>
@endpush

@extends('global.body')

@section('content')
    <h2>{% $name %} view</h2>
@stop
