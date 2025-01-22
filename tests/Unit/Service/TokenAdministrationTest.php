<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Service;

use OxidEsales\GraphQL\Base\DataType\Filter\IDFilter;
use OxidEsales\GraphQL\Base\DataType\Pagination\Pagination;
use OxidEsales\GraphQL\Base\DataType\Sorting\TokenSorting;
use OxidEsales\GraphQL\Base\DataType\TokenFilterList;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy as LegacyInfrastructure;
use OxidEsales\GraphQL\Base\Infrastructure\ModuleSetup;
use OxidEsales\GraphQL\Base\Infrastructure\Repository as BaseRepository;
use OxidEsales\GraphQL\Base\Infrastructure\Token as TokenInfrastructure;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\Authorization;
use OxidEsales\GraphQL\Base\Service\TokenAdministration;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;
use TheCodingMachine\GraphQLite\Types\ID;

class TokenAdministrationTest extends BaseTestCase
{
    public function testQueryTokensNotAuthorizedFilterOnNotOwnUserId(): void
    {
        $repository = $this->createPartialMock(BaseRepository::class, ['getList']);
        $repository->method('getList')->willReturn([]);

        $authorizationService = $this->createPartialMock(Authorization::class, ['isAllowed']);
        $authorizationService->method('isAllowed')->willReturn(false);

        $user = new UserDataType($this->getUserModelStub('_testuserid'));
        $authenticationService = $this->createPartialMock(Authentication::class, ['getUser']);
        $authenticationService->method('getUser')->willReturn($user);

        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, []);
        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, []);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, []);

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );
        $tokenFilterList = new TokenFilterList(new IDFilter(new ID('unknown')));
        $tokenSorting = TokenSorting::fromUserInput();
        $pagination = new Pagination();

        $this->expectException(InvalidLogin::class);
        $tokenAdministration->tokens($tokenFilterList, $pagination, $tokenSorting);
    }

    public function testQueryTokensNotAuthorizedFilterOnOwnUserId(): void
    {
        $repository = $this->createPartialMock(BaseRepository::class, ['getList']);
        $repository->method('getList')->willReturn([]);

        $authorizationService = $this->createPartialMock(Authorization::class, ['isAllowed']);
        $authorizationService->method('isAllowed')->willReturn(false);

        $user = new UserDataType($this->getUserModelStub('_testuserid'));
        $authenticationService = $this->createPartialMock(Authentication::class, ['getUser']);
        $authenticationService->method('getUser')->willReturn($user);

        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, []);
        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, []);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, []);

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );
        $tokenFilterList = new TokenFilterList(new IDFilter(new ID('_testuserid')));
        $tokenSorting = TokenSorting::fromUserInput();
        $pagination = new Pagination();

        $tokenAdministration->tokens($tokenFilterList, $pagination, $tokenSorting);
    }

    public function testCustomerTokensDeleteOwnByDefault(): void
    {
        $user = new UserDataType($this->getUserModelStub('_testuserid'));

        $repository = $this->createPartialMock(BaseRepository::class, ['getById']);
        $repository->method('getById')->willReturn($user);

        $authorizationService = $this->createPartialMock(Authorization::class, ['isAllowed']);
        $authorizationService->method('isAllowed')->willReturn(false);

        $authenticationService = $this->createPartialMock(Authentication::class, ['getUser']);
        $authenticationService->method('getUser')->willReturn($user);

        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, ['tokenDelete']);
        $tokenInfrastructure->method('tokenDelete')->with($user)->willReturn(5);

        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, []);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, []);

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );

        $this->assertEquals(5, $tokenAdministration->customerTokensDelete(null));
    }

    public function testCustomerTokensDeleteOwnWithId(): void
    {
        $user = new UserDataType($this->getUserModelStub('_testuserid'));

        $repository = $this->createPartialMock(BaseRepository::class, ['getById']);
        $repository->method('getById')->willReturn($user);

        $authorizationService = $this->createPartialMock(Authorization::class, ['isAllowed']);
        $authorizationService->method('isAllowed')->willReturn(false);

        $authenticationService = $this->createPartialMock(Authentication::class, ['getUser']);
        $authenticationService->method('getUser')->willReturn($user);

        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, ['tokenDelete']);
        $tokenInfrastructure->method('tokenDelete')->with($user)->willReturn(5);

        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, []);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, []);

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );

        $this->assertEquals(5, $tokenAdministration->customerTokensDelete(new ID('_testuserid')));
    }

    public function testCustomerTokensDeleteOtherUserFails(): void
    {
        $repository = $this->createPartialMock(BaseRepository::class, []);

        $authorizationService = $this->createPartialMock(Authorization::class, ['isAllowed']);
        $authorizationService->method('isAllowed')->willReturn(false);

        $user = new UserDataType($this->getUserModelStub('_testuserid'));
        $authenticationService = $this->createPartialMock(Authentication::class, ['getUser']);
        $authenticationService->method('getUser')->willReturn($user);

        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, []);
        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, []);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, []);

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );

        $this->expectException(InvalidLogin::class);
        $tokenAdministration->customerTokensDelete(new ID('_otheruserid'));
    }

    public function testCustomerTokensDeleteOtherUserAdmin(): void
    {
        $user = new UserDataType($this->getUserModelStub('_testuserid'));

        $repository = $this->createPartialMock(BaseRepository::class, ['getById']);
        $repository->method('getById')->willReturn($user);

        $authorizationService = $this->createPartialMock(Authorization::class, ['isAllowed']);
        $authorizationService->method('isAllowed')->willReturn(true);

        $authenticationService = $this->createPartialMock(Authentication::class, ['getUser']);
        $authenticationService->method('getUser')->willReturn($user);

        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, ['tokenDelete']);
        $tokenInfrastructure->method('tokenDelete')->with($user)->willReturn(5);

        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, []);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, []);

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );

        $this->assertEquals(5, $tokenAdministration->customerTokensDelete(new ID('_otheruserid')));
    }

    public function testShopTokensDelete(): void
    {
        $repository = $this->createPartialMock(BaseRepository::class, []);
        $authorizationService = $this->createPartialMock(Authorization::class, []);
        $authenticationService = $this->createPartialMock(Authentication::class, []);

        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, ['tokenDelete']);
        $tokenInfrastructure->method('tokenDelete')->with(null, null, 42)->willReturn(66);

        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, ['getShopId']);
        $legacyInfrastructure->method('getShopId')->willReturn(42);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, []);

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );

        $this->assertEquals(66, $tokenAdministration->shopTokensDelete());
    }

    public function testRegenerateSignatureKey(): void
    {
        $repository = $this->createPartialMock(BaseRepository::class, []);
        $authorizationService = $this->createPartialMock(Authorization::class, []);
        $authenticationService = $this->createPartialMock(Authentication::class, []);
        $tokenInfrastructure = $this->createPartialMock(TokenInfrastructure::class, []);
        $legacyInfrastructure = $this->createPartialMock(LegacyInfrastructure::class, []);
        $moduleSetup = $this->createPartialMock(ModuleSetup::class, ['runSetup']);
        $moduleSetup->expects($this->once())->method('runSetup');

        $tokenAdministration = new TokenAdministration(
            $repository,
            $authorizationService,
            $authenticationService,
            $tokenInfrastructure,
            $legacyInfrastructure,
            $moduleSetup
        );

        $this->assertTrue($tokenAdministration->regenerateSignatureKey());
    }
}
