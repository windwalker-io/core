<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Auth;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Authentication\AuthenticationInterface;
use Windwalker\Authentication\AuthResult;
use Windwalker\Authentication\ResultSet;
use Windwalker\Authorization\AuthorizationInterface;
use Windwalker\Core\Auth\AuthService;

#[CoversClass(AuthService::class)]
class AuthServiceTest extends TestCase
{
    private AuthenticationInterface $authentication;

    private AuthorizationInterface $authorization;

    private AuthService $instance;

    #[Before]
    protected function prepareInstance(): void
    {
        $this->authentication = $this->createStub(AuthenticationInterface::class);
        $this->authorization = $this->createStub(AuthorizationInterface::class);
        $this->instance = new AuthService($this->authentication, $this->authorization);
    }

    #[Test]
    public function authenticateReturnsMatchedResultAndFillsResultSetOnSuccess(): void
    {
        $credential = ['username' => 'foo', 'password' => 'bar'];
        $success = AuthResult::success($credential);
        $resultSet = new ResultSet(['db' => $success]);
        $resultSet->matchedMethod = 'db';

        $authentication = $this->createMock(AuthenticationInterface::class);
        $authentication->expects($this->once())
            ->method('authenticate')
            ->with($credential)
            ->willReturn($resultSet);

        $this->instance = new AuthService($authentication, $this->authorization);

        $result = $this->instance->authenticate($credential, $out);

        self::assertSame($success, $result);
        self::assertSame($resultSet, $out);
    }

    #[Test]
    public function authenticateReturnsFalseAndFillsResultSetOnFailure(): void
    {
        $credential = ['username' => 'foo', 'password' => 'wrong'];
        $failure = AuthResult::invalidPassword($credential);
        $resultSet = new ResultSet(['db' => $failure]);

        $authentication = $this->createStub(AuthenticationInterface::class);
        $authentication->method('authenticate')->willReturn($resultSet);

        $this->instance = new AuthService($authentication, $this->authorization);

        $result = $this->instance->authenticate($credential, $out);

        self::assertFalse($result);
        self::assertSame($resultSet, $out);
    }

    #[Test]
    public function authorizeUsesProvidedUserWithoutRetrievingOne(): void
    {
        $this->instance->setUserRetrieveHandler(
            function () {
                self::fail('User retrieve handler should not be called when a user is provided.');
            }
        );

        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects($this->once())
            ->method('authorize')
            ->with('edit', 'given-user', 'extra')
            ->willReturn(true);

        $this->instance->setAuthorization($authorization);

        $result = $this->instance->authorize('edit', 'given-user', 'extra');

        self::assertTrue($result);
    }

    #[Test]
    public function authorizeRetrievesUserWhenNoneProvided(): void
    {
        $this->instance->setUserRetrieveHandler(
            fn($conditions, $authService) => 'resolved-user'
        );

        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects($this->once())
            ->method('authorize')
            ->with('edit', 'resolved-user')
            ->willReturn(false);

        $this->instance->setAuthorization($authorization);

        $result = $this->instance->authorize('edit');

        self::assertFalse($result);
    }

    #[Test]
    public function cannotNegatesAuthorizeResultWhenAuthorized(): void
    {
        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('authorize')->willReturn(true);
        $this->instance->setAuthorization($authorization);

        self::assertFalse($this->instance->cannot('edit', 'someone'));
    }

    #[Test]
    public function cannotNegatesAuthorizeResultWhenNotAuthorized(): void
    {
        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('authorize')->willReturn(false);
        $this->instance->setAuthorization($authorization);

        self::assertTrue($this->instance->cannot('view', 'someone'));
    }

    #[Test]
    public function getAuthenticationReturnsConfiguredInstance(): void
    {
        self::assertSame($this->authentication, $this->instance->getAuthentication());
    }

    #[Test]
    public function getAuthorizationReturnsConfiguredInstance(): void
    {
        self::assertSame($this->authorization, $this->instance->getAuthorization());
    }

    #[Test]
    public function getUserDelegatesConditionsAndSelfToHandler(): void
    {
        $receivedArgs = [];

        $this->instance->setUserRetrieveHandler(
            function (...$args) use (&$receivedArgs) {
                $receivedArgs = $args;

                return 'the-user';
            }
        );

        $result = $this->instance->getUser(['id' => 1]);

        self::assertSame('the-user', $result);
        self::assertSame([['id' => 1], $this->instance], $receivedArgs);
    }

    #[Test]
    public function getUserRetrieveHandlerLazilyCreatesAndReusesDefaultHandler(): void
    {
        $handler = $this->instance->getUserRetrieveHandler();

        self::assertNull($handler());
        self::assertSame($handler, $this->instance->getUserRetrieveHandler());
    }
}
