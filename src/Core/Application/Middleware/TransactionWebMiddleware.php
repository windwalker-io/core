<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Core\Database\DatabaseAdapter;
use Windwalker\Middleware\MiddlewareInterface;
use function Windwalker\tap;

/**
 * The TransactionWebMiddleware class.
 *
 * @since  3.5
 */
class TransactionWebMiddleware extends AbstractWebMiddleware
{
    /**
     * Property db.
     *
     * @var  DatabaseAdapter
     */
    protected $db;

    /**
     * Property methods.
     *
     * @var  array|string
     */
    protected $methods;

    /**
     * TransactionWebMiddleware constructor.
     *
     * @param DatabaseAdapter $db
     * @param array|string    $methods
     */
    public function __construct(DatabaseAdapter $db, $methods = ['post', 'put', 'patch', 'delete'])
    {
        $this->db = $db;
        $this->methods = array_map('strtolower', (array) $methods);
    }

    /**
     * Middleware logic to be invoked.
     *
     * @param   Request                      $request  The request.
     * @param   Response                     $response The response.
     * @param   callable|MiddlewareInterface $next     The next middleware.
     *
     * @return  Response
     * @throws \Throwable
     */
    public function __invoke(Request $request, Response $response, $next = null)
    {
        $method = $request->getQueryParams()['_method'] ?? $request->getMethod();

        $method = strtolower($method);

        if ($this->methods !== ['*'] && !in_array($method, $this->methods, true)) {
            return $next($request, $response);
        }

        $trans = $this->db->getTransaction()->start();

        try {
            return tap($next($request, $response), function () use ($trans) {
                $trans->commit();
            });
        } catch (\Throwable $e) {
            $trans->rollback();

            throw $e;
        }
    }
}
