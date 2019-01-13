<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Windwalker\Core\Database\DatabaseAdapter;
use Windwalker\Middleware\MiddlewareInterface;

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
     * TransactionWebMiddleware constructor.
     *
     * @param DatabaseAdapter $db
     */
    public function __construct(DatabaseAdapter $db)
    {
        $this->db = $db;
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
        $trans = $this->db->getTransaction()->start();

        try {
            $response = $next($request, $response);

            $trans->commit();

            return $response;
        } catch (\Throwable $e) {
            $trans->rollback();

            throw $e;
        }
    }
}
