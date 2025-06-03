<?php

declare(strict_types=1);

namespace Windwalker\Core\Auth;

use DomainException;
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
     * @param  AuthenticationInterface|null  $authentication
     * @param  AuthorizationInterface|null   $authorization
     */
    public function __construct(
        protected ?AuthenticationInterface $authentication = null,
        protected ?AuthorizationInterface $authorization = null
    ) {
        //
    }

    public function authenticate(array $credential, ?ResultSet &$resultSet = null): false|AuthResult
    {
        $resultSet = $this->getAuthentication()->authenticate($credential);

        if (!$resultSet->isSuccess()) {
            return false;
        }

        return $resultSet->getMatchedResult();
    }

    public function authorize(string|\UnitEnum $policy, mixed $user = null, ...$args): bool
    {
        $user ??= $this->getUser($user);

        return $this->getAuthorization()->authorize($policy, $user, ...$args);
    }

    public function can(string|\UnitEnum $policy, mixed $user = null, ...$args): bool
    {
        return $this->authorize($policy, $user, ...$args);
    }

    public function cannot(string|\UnitEnum $policy, mixed $user = null, ...$args): bool
    {
        return !$this->authorize($policy, $user, ...$args);
    }

    /**
     * @return Authentication
     */
    public function getAuthentication(): AuthenticationInterface
    {
        if (!interface_exists(AuthenticationInterface::class)) {
            throw new DomainException('Please install windwalker/authentication ^4.0 first.');
        }

        return $this->authentication;
    }

    /**
     * @return AuthorizationInterface
     */
    public function getAuthorization(): AuthorizationInterface
    {
        if (!interface_exists(AuthorizationInterface::class)) {
            throw new DomainException('Please install windwalker/authorization ^4.0 first.');
        }

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

    public function getUser(mixed $conditions = null): mixed
    {
        return $this->getUserRetrieveHandler()($conditions, $this);
    }

    /**
     * @return callable
     */
    public function getUserRetrieveHandler(): callable
    {
        return $this->userRetrieveHandler ??= static fn() => null;
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
