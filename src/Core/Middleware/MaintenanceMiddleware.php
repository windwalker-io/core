<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Application\Offline\MaintenanceManager;
use Windwalker\Core\CorePackage;
use Windwalker\Core\View\BaseVM;
use Windwalker\Core\View\View;
use Windwalker\Session\Session;

use function Windwalker\response;

/**
 * The SiteOfflineMiddleware class.
 */
class MaintenanceMiddleware implements MiddlewareInterface
{
    public function __construct(protected ApplicationInterface $app)
    {
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->app->isMaintenance()) {
            $maintenanceManager = $this->app->retrieve(MaintenanceManager::class);
            $appRequest = $this->app->retrieve(AppRequestInterface::class);

            $clientIp = $appRequest->getClientIP();

            $config = $maintenanceManager->getConfig();

            $ips = $config->getAllowedIps();

            if (in_array($clientIp, $ips, true)) {
                return $handler->handle($request);
            }

            $secret = $config->getSecret();

            if ($secret && $this->checkSessionHaaSecret($secret, $request)) {
                return $handler->handle($request);
            }

            $redirect = $config->getRedirect();

            if ($redirect) {
                return response()->redirect($redirect, 303);
            }

            $template = $config->getTemplate() ?: 'maintenance';
            $status = 503;

            $appContext = $this->app->retrieve(AppContext::class);
            /** @var View $view */
            $view = $appContext->make(BaseVM::class);
            $view->setLayout($template);
            $view->addPath(CorePackage::root() . '/../views/app');

            $res = $appContext->renderView(
                $view,
                [
                    'maintenanceConfig' => $config,
                ],
            );

            $res = $res->withStatus($status);

            return $res;
        }

        return $handler->handle($request);
    }

    protected function checkSessionHaaSecret(string $secret, ServerRequestInterface $request): bool
    {
        $session = $this->app->retrieve(Session::class);

        $userSecret = $session->get('maintenance_secret');

        if (!$userSecret) {
            $exists = $request->getQueryParams()[$secret] ?? null;

            if ($exists !== null) {
                $userSecret = $secret;
            }
        }

        if ($userSecret && $userSecret === $secret) {
            $session->set('maintenance_secret', $userSecret);
            return true;
        }

        return false;
    }
}
