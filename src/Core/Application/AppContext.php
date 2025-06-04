<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Stringable;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Application\Context\AppContextTrait;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Http\RequestInspector;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;
use Windwalker\Data\Collection;
use Windwalker\DI\Container;
use Windwalker\DI\Parameters;
use Windwalker\Filter\Traits\FilterAwareTrait;
use Windwalker\Session\Session;

use function Swoole\Coroutine\Http\get;

/**
 * The Context class.
 *
 * @method WebRootApplicationInterface getRootApp()
 *
 * @property-read AppState $state
 */
#[Immutable(Immutable::PROTECTED_WRITE_SCOPE)]
class AppContext implements WebApplicationInterface, AppContextInterface
{
    use FilterAwareTrait;
    use AppContextTrait;

    protected ?AppRequest $appRequest = null;

    /**
     * Context constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function dispatchWithInput(
        string|array|callable $controller,
        ServerRequestInterface|array|null $data = null,
        array $args = [],
    ): mixed {
        $controllerOrigin = $this->getController();

        $appReq = $this->getAppRequest();

        if ($data !== null) {
            if ($data instanceof AppRequestInterface) {
                $appReqNew = $data;
            } elseif ($data instanceof ServerRequestInterface) {
                $appReqNew = $appReq->withRequest($data);
            } else {
                $req = $appReq->getServerRequest();
                $req = $req->withQueryParams([]);
                $req = $req->withParsedBody($data);

                $appReqNew = $appReq->withRequest($req);
            }

            $this->setAppRequest($appReqNew);
        }

        $result = $this->dispatchController($controller, $args);

        $this->setController($controllerOrigin);

        if ($data !== null) {
            $this->setAppRequest($appReq);
        }

        return $result;
    }

    public function dispatchController(mixed $controller = null, array $args = []): mixed
    {
        if ($controller) {
            $this->setController($controller);
        }

        return $this->retrieve(ControllerDispatcher::class)
            ->dispatch($this, $args);
    }

    public function renderView(string|object $view, array $data = [], array $options = []): ResponseInterface
    {
        if (is_string($view)) {
            $view = $this->make($view);
        }

        if ($view instanceof ViewModelInterface) {
            $view = $this->make(
                View::class,
                [
                    'viewModel' => $view,
                    'options' => $options
                ]
            );
        }

        $options = [
            ...$view->getOptions(),
            ...$options
        ];

        $view->setOptions($options);

        return $view->render($data);
    }

    public function getRequestRawMethod(): string
    {
        return $this->appRequest->getMethod();
    }

    public function getRequestMethod(): string
    {
        if ($this->isApiCall()) {
            return $this->getRequestRawMethod();
        }

        return $this->appRequest->getOverrideMethod();
    }

    /**
     * @return Parameters|null
     */
    public function getParams(): ?Parameters
    {
        return $this->params;
    }

    /**
     * @param  Parameters|null  $params
     *
     * @return  static  Return self to support chaining.
     */
    public function setParams(?Parameters $params): static
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->appRequest->getUri();
    }

    /**
     * @param  UriInterface  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function sethUri(UriInterface $uri): static
    {
        $this->appRequest = $this->appRequest->withUri($uri);

        return $this;
    }

    /**
     * @return SystemUri
     */
    public function getSystemUri(): SystemUri
    {
        return $this->appRequest->getSystemUri();
    }

    /**
     * @param  SystemUri  $uri
     *
     * @return  static  Return self to support chaining.
     */
    public function setSystemUri(SystemUri $uri): static
    {
        $this->appRequest = $this->appRequest->withSystemUri($uri);

        return $this;
    }

    /**
     * @return AppRequest
     */
    public function getAppRequest(): AppRequest
    {
        return $this->appRequest;
    }

    /**
     * @param  AppRequest  $request
     *
     * @return  static  Return self to support chaining.
     */
    public function setAppRequest(AppRequest $request): static
    {
        $this->appRequest = $request;

        $request->addEventDealer($this);

        return $this;
    }

    /**
     * inputWithMethod
     *
     * @param  string  $method
     * @param  mixed   ...$fields
     *
     * @return  mixed|Collection
     */
    public function inputOfMethod(string $method = 'REQUEST', ...$fields): mixed
    {
        return $this->appRequest->inputWithMethod($method, ...$fields);
    }

    /**
     * file
     *
     * @param  mixed  ...$fields
     *
     * @return  UploadedFileInterface[]|UploadedFileInterface|array
     */
    public function file(...$fields): mixed
    {
        return $this->appRequest->file(...$fields);
    }

    /**
     * Redirect to another URL.
     *
     * @param  string|Stringable  $url
     * @param  int                $code
     * @param  bool               $instant
     *
     * @return  ResponseInterface
     */
    public function redirect(string|Stringable $url, int $code = 303, bool $instant = false): ResponseInterface
    {
        $systemUri = $this->getSystemUri();

        $url = $systemUri->absolute((string) $url);

        return $this->getRootApp()->redirect($url, $code, $instant);
    }

    public function addMessage(string|array $messages, ?string $type = 'info'): static
    {
        $this->service(Session::class)->addFlash($messages, $type ?? 'info');

        return $this;
    }

    public function getStage(): ?string
    {
        $matched = $this->getMatchedRoute();

        if (!$matched) {
            return null;
        }

        return (string) $matched->getExtraValue('namespace');
    }

    public function isApiCall(): bool
    {
        return $this->getAppRequest()->isApiCall();
    }

    /**
     * Close this request.
     *
     * @param  mixed  $return
     *
     * @return  void
     */
    #[NoReturn]
    public function close(
        mixed $return = ''
    ): void {
        $this->getRootApp()->close($return);
    }

    /**
     * @inheritDoc
     */
    public function __get(string $name)
    {
        if ($name === 'state') {
            return $this->$name;
        }

        return $this->magicGet($name);
    }
}
