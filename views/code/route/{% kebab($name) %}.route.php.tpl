{% $phpOpen %}

namespace App\Routes;

use Windwalker\Core\Router\RouteCreator;

/** @var RouteCreator $router */

$router->group('{% kebab($name) %}')
    ->register(function (RouteCreator $router) {
        $router->any('{% snake($name) %}', '/{% kebab($name) %}');
            //->controller()
            //->view();
    });
