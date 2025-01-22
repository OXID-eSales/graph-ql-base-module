<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\Eshop\Application\Model\User as UserModel;
use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy as LegacyService;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\JwtConfigurationBuilder;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AuthenticationTest extends BaseTestCase
{
    // phpcs:enable

    /** @var LegacyService|MockObject */
    private $legacy;

    /** @var MockObject|TokenInfrastructure */
    private $tokenInfrastructure;

    /** @var JwtConfigurationBuilder */
    private $jwtConfigurationBuilder;

    /** @var TokenService */
    private $tokenService;

    public function setUp(): void
    {
        $this->legacy = $this->getMockBuilder(LegacyService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenInfrastructure = $this->getTokenInfrastructureMock();

        $this->jwtConfigurationBuilder = new JwtConfigurationBuilder(
            $this->getModuleConfigurationMock(),
            $this->legacy
        );

        $this->tokenService = new TokenService(
            null,
            $this->jwtConfigurationBuilder,
            $this->legacy,
            new EventDispatcher(),
            $this->getModuleConfigurationMock(),
            $this->tokenInfrastructure,
            $this->getRefreshRepositoryMock()
        );
    }

    public function testIsLoggedWithoutToken(): void
    {
        $authenticationService = $this->getSut();
        $this->assertFalse($authenticationService->isLogged());
    }

    public function testIsLoggedWithNullToken(): void
    {
        $authenticationService = $this->getSut();
        $this->assertFalse($authenticationService->isLogged());
    }

    /**
     * @dataProvider providerValidCredentials
     */
    public function testIsLoggedWithValidToken(string $username, string $password): void
    {
        $unencryptedToken = $this->createToken($username, $password);
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertTrue($authenticationService->isLogged());
    }

    public static function providerValidCredentials(): array
    {
        return [
            'admin' => [
                'username' => 'admin',
                'password' => 'admin',
            ],
        ];
    }

    public static function providerInvalidCredentials(): array
    {
        return [
            'user' => [
                'username' => 'user@oxid-esales.com',
                'password' => 'useruser',
            ],
            'not_existing' => [
                'username' => 'notauser@oxid-esales.com',
                'password' => 'notauseruser',
            ],
        ];
    }

    /**
     * @dataProvider providerValidCredentials
     *
     * @param mixed $username
     * @param mixed $password
     */
    public function testGetUser($username, $password): void
    {
        $unencryptedToken = $this->createToken($username, $password);
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertInstanceOf(User::class, $authenticationService->getUser());
    }

    /**
     * @dataProvider providerInvalidCredentials
     *
     * @param mixed $username
     * @param mixed $password
     */
    public function testGetUserNameWithInvalidCredentials($username, $password): void
    {
        $this->expectException(InvalidLogin::class);

        $this->legacy
            ->method('login')
            ->willThrowException(new InvalidLogin('Username/password combination is invalid'));
        $this->legacy
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacy
            ->method('getShopId')
            ->willReturn(1);

        $unencryptedToken = $this->tokenService->createToken($username, $password);
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertEmpty($authenticationService->getUser()->email());
    }

    /**
     * @dataProvider providerValidCredentials
     *
     * @param mixed $username
     * @param mixed $password
     */
    public function testGetUserNameWithValidCredentials($username, $password): void
    {
        $unencryptedToken = $this->createToken($username, $password);
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertSame($username, $authenticationService->getUser()->email());
    }

    public function testGetUserNameForNullToken(): void
    {
        $authenticationService = $this->getAuthenticationService();

        $this->assertEmpty($authenticationService->getUser()->email());
    }

    public function testGetUserId(): void
    {
        $userModel = $this->getUserModelStub('the_admin_oxid');

        $this->legacy->method('login')->willReturn(new User($userModel));
        $this->legacy->method('getUserModel')->with('the_admin_oxid')->willReturn($userModel);

        $unencryptedToken = $this->tokenService->createToken('admin', 'admin');
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertSame('the_admin_oxid', $authenticationService->getUser()->id()->val());
        $this->assertNotNull($authenticationService->getUser()->email());
    }

    public function testGetUserIdForNullToken(): void
    {
        $userModel = $this->getUserModelStub();
        $this->legacy->method('getUserModel')->with('')->willReturn($userModel);

        $authenticationService = $this->getAuthenticationService();

        $user = $authenticationService->getUser();

        $this->assertEmpty($user->id()->val());
    }

    public function testGetUserIdForAnonymousToken(): void
    {
        $user = $this->getUserModelStub('someRandomId');

        $this->legacy->method('login')->willReturn(
            new User($user, true)
        );

        $this->legacy->method('getUserModel')->willReturn($user);

        $unencryptedToken = $this->tokenService->createToken();
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertNotEmpty($authenticationService->getUser()->id()->val());
    }

    public function testIsLoggedWithAnonymousToken(): void
    {
        $this->legacy
            ->method('login')
            ->willReturn(
                new User($this->getUserModelStub(), true)
            );
        $this->legacy
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacy
            ->method('getShopId')
            ->willReturn(1);
        $this->legacy
            ->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $unencryptedToken = $this->tokenService->createToken();
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertFalse($authenticationService->isLogged());
    }

    public function testIsUserAnonymous(): void
    {
        $this->legacy
            ->method('login')
            ->willReturn(
                new User($this->getUserModelStub(), true)
            );
        $this->legacy
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacy
            ->method('getShopId')
            ->willReturn(1);
        $this->legacy
            ->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $unencryptedToken = $this->tokenService->createToken();
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertTrue($authenticationService->getUser()->isAnonymous());
    }

    /**
     * @dataProvider providerValidCredentials
     */
    public function testLoggedUserIsNotAnonymous(string $username, string $password): void
    {
        $unencryptedToken = $this->createToken($username, $password);
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertFalse($authenticationService->getUser()->isAnonymous());
    }

    public function testIsAnonymousWithNullToken(): void
    {
        $authenticationService = $this->getSut();

        $this->assertFalse($authenticationService->getUser()->isAnonymous());
    }

    /**
     * @dataProvider providerInvalidCredentials
     */
    public function testIsAnonymousWithWrongCredentials(string $username, string $password): void
    {
        $this->expectException(InvalidLogin::class);

        $this->legacy
            ->method('login')
            ->willThrowException(new InvalidLogin('Username/password combination is invalid'));
        $this->legacy
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacy
            ->method('getShopId')
            ->willReturn(1);

        $unencryptedToken = $this->tokenService->createToken($username, $password);
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertFalse($authenticationService->getUser()->isAnonymous());
    }

    public function testGetUserNameForAnonymousToken(): void
    {
        $this->legacy
            ->method('login')
            ->willReturn(
                new User($this->getUserModelStub(), true)
            );
        $this->legacy
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacy
            ->method('getShopId')
            ->willReturn(1);
        $this->legacy
            ->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $unencryptedToken = $this->tokenService->createToken();
        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertEmpty($authenticationService->getUser()->email());
    }

    public function testLoggedUserInAnonymousGroup(): void
    {
        $this->legacy->method('login')->willReturn(
            new User($this->getUserModelStub(), true)
        );

        $this->legacy->method('getUserGroupIds')
            ->willReturn(['oxidanonymous']);

        $unencryptedToken = $this->tokenService->createToken('admin', 'admin');

        $authenticationService = $this->getSut($unencryptedToken);

        $this->assertTrue($authenticationService->getUser()->isAnonymous());
    }

    protected function getSut(?UnencryptedToken $unencryptedToken = null, ?string $lifetime = null): Authentication
    {
        return new Authentication(
            $this->legacy,
            new TokenService(
                $unencryptedToken,
                $this->jwtConfigurationBuilder,
                $this->legacy,
                new EventDispatcher(),
                $this->getModuleConfigurationMock(),
                $this->tokenInfrastructure,
                $this->getRefreshRepositoryMock()
            )
        );
    }

    private function getAuthenticationService(): Authentication
    {
        $this->legacy
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacy
            ->method('getShopId')
            ->willReturn(1);

        return $this->getSut();
    }

    private function createToken(string $username, string $password): UnencryptedToken
    {
        $userModel = $this->createPartialMock(UserModel::class, ['getRawFieldData']);
        $userModel->method('getRawFieldData')->withAnyParameters()->willReturn($username);
        $user = $this->getUserDataStub($userModel);

        $this->legacy
            ->method('login')
            ->willReturn($user);
        $this->legacy
            ->method('getShopUrl')
            ->willReturn('https://whatever.com');
        $this->legacy
            ->method('getShopId')
            ->willReturn(1);
        $this->legacy
            ->method('getUserModel')
            ->willReturn($userModel);

        return $this->tokenService->createToken($username, $password);
    }
}
