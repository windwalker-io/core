{% $phpOpen %}

namespace App\Routes;

use Windwalker\Core\Router\RouteCreator;

/** @var RouteCreator $router */

$router->group('{% strtolower($name) %}')
    ->register(function (RouteCreator $router) {
        $router->any('{% snake($name) %}', '/{% kebab($name) %}');
            //->controller()
            //->view();
    });
