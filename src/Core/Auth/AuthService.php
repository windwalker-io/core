<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Auth;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\AuthenticationInterface;
use Windwalker\Authentication\AuthResult;
use Windwalker\Authentication\ResultSet;
use Windwalker\Authorisation\AuthorisationInterface;

/**
 * The AuthService class.
 */
class AuthService
{
    /**
     * AuthService constructor.
     *
     * @param  AuthenticationInterface  $authentication
     * @param  AuthorisationInterface   $authorisation
     */
    public function __construct(
        protected AuthenticationInterface $authentication,
        protected AuthorisationInterface $authorisation
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
        return $this->authorisation->authorise($policy, $user, ...$args);
    }

    /**
     * @return Authentication
     */
    public function getAuthentication(): AuthenticationInterface
    {
        return $this->authentication;
    }

    /**
     * @return AuthorisationInterface
     */
    public function getAuthorisation(): AuthorisationInterface
    {
        return $this->authorisation;
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
     * @param  AuthorisationInterface  $authorisation
     *
     * @return  static  Return self to support chaining.
     */
    public function setAuthorisation(AuthorisationInterface $authorisation): static
    {
        $this->authorisation = $authorisation;

        return $this;
    }
}
