<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Controller;

use OxidEsales\GraphQL\Base\Controller\Token as TokenController;
use OxidEsales\GraphQL\Base\DataType\Filter\DateFilter;
use OxidEsales\GraphQL\Base\DataType\Filter\IDFilter;
use OxidEsales\GraphQL\Base\DataType\Pagination\Pagination;
use OxidEsales\GraphQL\Base\DataType\Sorting\TokenSorting;
use OxidEsales\GraphQL\Base\DataType\TokenFilterList;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\Authorization;
use OxidEsales\GraphQL\Base\Service\RefreshTokenServiceInterface;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use OxidEsales\GraphQL\Base\Service\TokenAdministration as TokenAdministration;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;
use TheCodingMachine\GraphQLite\Types\ID;

//todo: tests do not do any assertions, fix it.
class TokenTest extends BaseTestCase
{
    public function testTokensQueryWithDefaultFilters(): void
    {
        $authentication = $this->createPartialMock(Authentication::class, ['getUser']);
        $authentication->method('getUser')
            ->willReturn(new UserDataType($this->getUserModelStub('_testuserid')));

        $tokenAdministration = $this->createPartialMock(TokenAdministration::class, ['tokens']);
        $tokenAdministration->method('tokens')
            ->with(
                TokenFilterList::fromUserInput(new IDFilter($authentication->getUser()->id())),
                new Pagination(),
                TokenSorting::fromUserInput(TokenSorting::SORTING_ASC)
            )
            ->willReturn([]);

        $tokenController = $this->getTokenController(
            tokenAdministration: $tokenAdministration,
            authentication: $authentication
        );
        $tokenController->tokens();
    }

    public function testTokensQueryWithCustomFilters(): void
    {
        $authentication = $this->createPartialMock(Authentication::class, ['getUser']);
        $authentication->method('getUser')
            ->willReturn(new UserDataType($this->getUserModelStub('_testuserid')));

        $tokenFilterList = TokenFilterList::fromUserInput(
            new IDFilter(new ID('someone_else')),
            new IDFilter(new ID(1)),
            new DateFilter(null, ['2021-01-12 12:12:12', '2021-12-31 12:12:12'])
        );
        $tokenSorting = TokenSorting::fromUserInput(TokenSorting::SORTING_DESC);
        $pagination = Pagination::fromUserInput(10, 20);

        $tokenAdministration = $this->createPartialMock(TokenAdministration::class, ['tokens']);
        $tokenAdministration->method('tokens')
            ->with(
                $tokenFilterList,
                $pagination,
                $tokenSorting
            )
            ->willReturn([]);

        $tokenController = $this->getTokenController(
            tokenAdministration: $tokenAdministration,
            authentication: $authentication
        );
        $tokenController->tokens($tokenFilterList, $pagination, $tokenSorting);
    }

    public function testCustomerTokensDelete(): void
    {
        $authentication = $this->createPartialMock(Authentication::class, []);
        $tokenAdministration = $this->createPartialMock(TokenAdministration::class, ['customerTokensDelete']);
        $tokenAdministration->method('customerTokensDelete')
            ->willReturn(5);

        $tokenController = $this->getTokenController(
            tokenAdministration: $tokenAdministration,
            authentication: $authentication
        );
        $tokenController->customerTokensDelete(new ID('someUserId'));
    }

    public function testTokenDelete(): void
    {
        $authorization = $this->createPartialMock(Authorization::class, ['isAllowed']);
        $authorization->method('isAllowed')
            ->willReturn(true);

        $tokenController = $this->getTokenController(
            authorization: $authorization
        );
        $tokenController->tokenDelete(new ID('someTokenId'));
    }

    public function testRefreshGivesStringValueOfNewToken(): void
    {
        $token = $this->getTokenController(
            refreshTokenService: $refreshTokenServiceMock = $this->createMock(RefreshTokenServiceInterface::class),
        );

        $refreshToken = uniqid();
        $fingerprintHash = uniqid();
        $newRefreshToken = uniqid();

        $refreshTokenServiceMock->method('refreshToken')
            ->with($refreshToken, $fingerprintHash)->willReturn($newRefreshToken);

        $this->assertSame($newRefreshToken, $token->refresh($refreshToken, $fingerprintHash));
    }

    private function getTokenController(
        TokenAdministration $tokenAdministration = null,
        Authentication $authentication = null,
        Authorization $authorization = null,
        TokenService $tokenService = null,
        RefreshTokenServiceInterface $refreshTokenService = null,
    ): TokenController {
        return new TokenController(
            tokenAdministration: $tokenAdministration ?? $this->createStub(TokenAdministration::class),
            authentication: $authentication ?? $this->createStub(Authentication::class),
            authorization: $authorization ?? $this->createStub(Authorization::class),
            tokenService: $tokenService ?? $this->createStub(TokenService::class),
            refreshTokenService: $refreshTokenService ?? $this->createStub(RefreshTokenServiceInterface::class),
        );
    }
}
