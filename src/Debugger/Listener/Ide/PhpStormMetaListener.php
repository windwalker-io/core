<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Listener\Ide;

use Windwalker\Core\Router\MainRouter;
use Windwalker\Event\Event;
use Windwalker\Filesystem\File;
use Windwalker\Filesystem\Path\PathCollection;
use Windwalker\Filesystem\Path\PathLocator;
use Windwalker\Ioc;
use Windwalker\String\StringHelper;
use Windwalker\Structure\Structure;

/**
 * The PhpStormListener class.
 *
 * @since  3.0
 */
class PhpStormMetaListener
{
    /**
     * Property tmpl.
     *
     * @var  string
     */
    protected $tmpl = <<<TMPL
<?php
    
namespace PHPSTORM_META
{
    \$STATIC_METHOD_TYPES = [
        \Windwalker\Application\AbstractApplication::get('') => [
            {config}
        ],
        \Windwalker\Core\Config\Config::get('') => [
            {config}
        ],
        new \Windwalker\Core\Config\Config => [
            {config}
        ]
    ];
}
TMPL;

    /**
     * onAfterExecute
     *
     * @param Event $event
     *
     * @return  void
     */
    public function onAfterRender(Event $event)
    {
        $config = Ioc::getConfig();

        $array = $config->flatten();

        $keys = array_map(function ($value) {
            return StringHelper::quote($value) . ' instanceof mixed';
        }, array_keys($array));

        $data = str_replace('{config}', implode(",\n", $keys), $this->tmpl);

        File::write(WINDWALKER_TEMP . '/ide/.phpstorm.meta.php', $data);

        // For php toolbox
        $tmpl = <<<TMPL
{
    "registrar": [
        {
            "language": "php",
            "provider": "config",
            "signature": [
                "Windwalker\\\\Core\\\\Config\\\\Config:get",
                "Windwalker\\\\Core\\\\Config\\\\Config:set",
                "Windwalker\\\\Application\\\\AbstractApplication:get",
                "Windwalker\\\\Application\\\\AbstractApplication:set",
            ]
        }
    ],
    "providers": [
        {
            "name": "config",
            "lookup_strings": [
                {config}
            ]
        }
    ]
}
TMPL;

        $array = $config->flatten();

        $keys = array_map(function ($value) {
            return StringHelper::quote($value, '"');
        }, array_keys($array));

        $data = str_replace('{config}', implode(",\r\n", $keys), $tmpl);

        File::write(WINDWALKER_TEMP . '/ide/config/.ide-toolbox.metadata.json', $data);
    }

    /**
     * onAfterRouting
     *
     * @param Event $event
     *
     * @return  void
     *
     * @since  __DEPLOY_VERSION__
     */
    public function onAfterRouting(Event $event)
    {
        $tmpl = <<<TMPL
{
    "registrar": [
        {
            "language": "php",
            "provider": "routing",
            "signature": [
                "Windwalker\\\\Core\\\\Router\\\\RouteBuilderTrait:to",
                "Windwalker\\\\Core\\\\Router\\\\RouteBuilderTrait:route",
                "Windwalker\\\\Core\\\\Router\\\\RouteBuilderTrait:generate",
                "Windwalker\\\\Core\\\\Router\\\\RouteBuilderTrait:fullRoute",
                "Windwalker\\\\Core\\\\Router\\\\RouteBuilderTrait:rawRoute",
                "Windwalker\\\\Core\\\\Router\\\\CoreRouter:to",
                "Windwalker\\\\Core\\\\Router\\\\CoreRouter:route",
                "Windwalker\\\\Core\\\\Router\\\\CoreRouter:generate",
                "Windwalker\\\\Core\\\\Router\\\\CoreRouter:fullRoute",
                "Windwalker\\\\Core\\\\Router\\\\CoreRouter:rawRoute"
            ]
        }
    ],
    "providers": [
        {
            "name": "routing",
            "lookup_strings": [
                %s
            ]
        }
    ]
}
TMPL;

        /** @var MainRouter $router */
        $router = $event['router'];

        $names = [];
        $routes = $router->getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();
            
            list($pkg, $name) = array_pad(explode('@', $name), -2, null);
            
            $names[] = StringHelper::quote($name, '"');
            
            if ($pkg) {
                $names[] = StringHelper::quote($pkg . '@' . $name, '"');
            }
        }
        
        $names = implode(",\r\n", $names);
        $content = sprintf($tmpl, $names);

        File::write(WINDWALKER_TEMP . '/ide/router/.ide-toolbox.metadata.json', $content);
    }
}
