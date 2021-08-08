<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Auth;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\AuthenticationInterface;
use Windwalker\Authentication\AuthResult;
use Windwalker\Authentication\ResultSet;
use Windwalker\Authorization\AuthorizationInterface;

/**
 * The AuthService class.
 */
class AuthService
{
    /**
     * AuthService constructor.
     *
     * @param  AuthenticationInterface  $authentication
     * @param  AuthorizationInterface   $authorization
     */
    public function __construct(
        protected AuthenticationInterface $authentication,
        protected AuthorizationInterface $authorization
    ) {
        //
    }

    public function authenticate(array $credential, ResultSet &$resultSet = null): false|AuthResult
    {
        $resultSet = $this->authentication->authenticate($credential);

        if (!$resultSet->isSuccess()) {
            return false;
        }

        return $resultSet->getMatchedResult();
    }

    public function authorise(string $policy, mixed $user, ...$args): bool
    {
        return $this->authorization->authorise($policy, $user, ...$args);
    }

    /**
     * @return Authentication
     */
    public function getAuthentication(): AuthenticationInterface
    {
        return $this->authentication;
    }

    /**
     * @return AuthorizationInterface
     */
    public function getAuthorization(): AuthorizationInterface
    {
        return $this->authorization;
    }

    /**
     * @param  AuthenticationInterface  $authentication
     *
     * @return  static  Return self to support chaining.
     */
    public function setAuthentication(AuthenticationInterface $authentication): static
    {
        $this->authentication = $authentication;

        return $this;
    }

    /**
     * @param  AuthorizationInterface  $authorization
     *
     * @return  static  Return self to support chaining.
     */
    public function setAuthorization(AuthorizationInterface $authorization): static
    {
        $this->authorization = $authorization;

        return $this;
    }
}
