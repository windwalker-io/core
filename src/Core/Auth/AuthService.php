<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
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
     * @var callable
     */
    protected $userRetrieveHandler;

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

    public function authorize(string $policy, mixed $user = null, ...$args): bool
    {
        $user ??= $this->getUser();

        return $this->authorization->authorize($policy, $user, ...$args);
    }

    public function can(string $policy, mixed $user = null, ...$args): bool
    {
        return $this->authorize($policy, $user, $args);
    }

    public function cannot(string $policy, mixed $user = null, ...$args): bool
    {
        return !$this->authorize($policy, $user, $args);
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

    public function getUser(): mixed
    {
        return $this->getUserRetrieveHandler()($this);
    }

    /**
     * @return callable
     */
    public function getUserRetrieveHandler(): callable
    {
        return $this->userRetrieveHandler ??= static fn () => null;
    }

    /**
     * @param  callable  $userRetrieveHandler
     *
     * @return  static  Return self to support chaining.
     */
    public function setUserRetrieveHandler(callable $userRetrieveHandler): static
    {
        $this->userRetrieveHandler = $userRetrieveHandler;

        return $this;
    }
}
