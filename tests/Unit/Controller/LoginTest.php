<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Controller;

use OxidEsales\Eshop\Application\Model\User as UserModel;
use OxidEsales\GraphQL\Base\Controller\Login;
use OxidEsales\GraphQL\Base\DataType\LoginInterface;
use OxidEsales\GraphQL\Base\DataType\User;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\JwtConfigurationBuilder;
use OxidEsales\GraphQL\Base\Service\LoginServiceInterface;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LoginTest extends BaseTestCase
{
    /** @var Legacy|MockObject */
    private $legacy;

    /** @var JwtConfigurationBuilder */
    private $jwtConfigurationBuilder;

    /** @var TokenService */
    private $tokenService;

    /** @var MockObject|TokenInfrastructure */
    private $tokenInfrastructure;

    public function setUp(): void
    {
        $this->legacy = $this->getMockBuilder(Legacy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->legacy->method('getShopUrl')->willReturn('https://whatever.com');
        $this->legacy->method('getShopId')->willReturn(1);

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
        );
    }

    public function testCreateTokenWithValidCredentials(): void
    {
        $username = $password = 'admin';

        $userModelStub = $this->createPartialMock(UserModel::class, ['getRawFieldData', 'isLoaded']);
        $userModelStub->setId('someTestAdminId');
        $userModelStub->method('getRawFieldData')->with('oxusername')->willReturn('someTestUsername');
        $userModelStub->method('isLoaded')->willReturn(true);
        $user = new User($userModelStub);

        $this->legacy->method('login')->with($username, $password)->willReturn($user);
        $this->legacy->method('createUniqueIdentifier')->willReturn(uniqid());

        $loginController = new Login(
            $this->tokenService,
            $this->getLoginService($this->legacy),
        );

        $jwt = $loginController->token($username, $password);
        $config = $this->jwtConfigurationBuilder->getConfiguration();
        $token = $config->parser()->parse($jwt);
        $validator = $config->validator();

        $this->assertTrue($validator->validate($token, ...$config->validationConstraints()));
        $this->assertEquals($user->id()->val(), $token->claims()->get(TokenService::CLAIM_USERID));
        $this->assertEquals($user->email(), $token->claims()->get(TokenService::CLAIM_USERNAME));
        $this->assertEquals(1, $token->claims()->get(TokenService::CLAIM_SHOPID));
        $this->assertNotEmpty($token->claims()->get(TokenService::CLAIM_TOKENID));
    }

    public function testCreateTokenWithMissingPassword(): void
    {
        $this->legacy->method('login')->willReturn(
            new User($this->getUserModelStub('someRandomId'), true)
        );

        $loginController = new Login(
            $this->tokenService,
            $this->getLoginService($this->legacy),
        );

        $jwt = $loginController->token('none');
        $config = $this->jwtConfigurationBuilder->getConfiguration();
        $token = $config->parser()->parse($jwt);
        $validator = $config->validator();

        $this->assertTrue($validator->validate($token, ...$config->validationConstraints()));
        $this->assertEquals(1, $token->claims()->get(TokenService::CLAIM_SHOPID));
        $this->assertNotEmpty($token->claims()->get(TokenService::CLAIM_USERID));
    }

    public function testCreateTokenWithMissingUsername(): void
    {
        $shop = [
            'url' => 'https://whatever.com',
            'id' => 1,
        ];

        $this->legacy->method('getShopUrl')->willReturn($shop['url']);
        $this->legacy->method('getShopId')->willReturn($shop['id']);
        $this->legacy->method('login')->willReturn(
            new User($this->getUserModelStub('someRandomId'), true)
        );

        $loginController = new Login(
            $this->tokenService,
            $this->getLoginService($this->legacy),
        );

        $jwt = $loginController->token(null, 'none');
        $config = $this->jwtConfigurationBuilder->getConfiguration();
        $token = $config->parser()->parse($jwt);
        $validator = $config->validator();

        $this->assertTrue($validator->validate($token, ...$config->validationConstraints()));
        $this->assertEquals($shop['id'], $token->claims()->get(TokenService::CLAIM_SHOPID));
        $this->assertNotEmpty($token->claims()->get(TokenService::CLAIM_USERID));
    }

    public function testCreateAnonymousToken(): void
    {
        $shop = [
            'url' => 'https://whatever.com',
            'id' => 1,
        ];

        $this->legacy->method('getShopUrl')->willReturn($shop['url']);
        $this->legacy->method('getShopId')->willReturn($shop['id']);
        $this->legacy->method('login')->willReturn(
            new User($this->getUserModelStub('someRandomId'), true)
        );

        $loginController = new Login(
            $this->tokenService,
            $this->getLoginService($this->legacy),
        );

        $jwt = $loginController->token();
        $config = $this->jwtConfigurationBuilder->getConfiguration();
        $token = $config->parser()->parse($jwt);
        $validator = $config->validator();

        $this->assertTrue($validator->validate($token, ...$config->validationConstraints()));
        $this->assertEquals($shop['id'], $token->claims()->get(TokenService::CLAIM_SHOPID));
        $this->assertNotEmpty($token->claims()->get(TokenService::CLAIM_USERID));
    }

    public function testLoginReturnsLoginServiceResult(): void
    {
        $loginController = new Login(
            tokenService: $this->createStub(TokenService::class),
            loginService: $loginServiceMock = $this->createMock(LoginServiceInterface::class),
        );

        $userName = uniqid();
        $password = uniqid();

        $loginDataTypeStub = $this->createStub(LoginInterface::class);
        $loginServiceMock->method('login')->with($userName, $password)->willReturn($loginDataTypeStub);

        $this->assertSame($loginDataTypeStub, $loginController->login($userName, $password));
    }
}
