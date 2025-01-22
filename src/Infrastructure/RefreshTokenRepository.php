<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Infrastructure;

use DateTimeImmutable;
use Doctrine\DBAL\ForwardCompatibility\Result;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Base\DataType\RefreshToken as RefreshTokenDataType;
use OxidEsales\GraphQL\Base\DataType\RefreshTokenInterface;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\DataType\UserInterface;
use OxidEsales\GraphQL\Base\Exception\InvalidRefreshToken;
use OxidEsales\GraphQL\Base\Infrastructure\Model\RefreshTokenModelFactoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private QueryBuilderFactoryInterface $queryBuilderFactory,
        private Legacy $legacy,
        private RefreshTokenModelFactoryInterface $refreshTokenModelFactory,
    ) {
    }

    public function getNewRefreshToken(string $userId, string $lifeTime): RefreshTokenInterface
    {
        $refreshToken = $this->refreshTokenModelFactory->create();

        $refreshToken->assign([
            'OXID' => $this->legacy->createUniqueIdentifier(),
            'OXSHOPID' => $this->legacy->getShopId(),
            'OXUSERID' => $userId,
            'ISSUED_AT' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            'EXPIRES_AT' => (new DateTimeImmutable($lifeTime))->format('Y-m-d H:i:s'),
            'TOKEN' => substr(bin2hex(random_bytes(128)), 0, 255),
        ]);
        $refreshToken->save();

        return new RefreshTokenDataType($refreshToken);
    }

    public function removeExpiredTokens(): void
    {
        $queryBuilder = $this->queryBuilderFactory->create()
            ->delete('oegraphqlrefreshtoken')
            ->where('EXPIRES_AT <= NOW()');

        $queryBuilder->execute();
    }

    private function getTokenUserId(string $refreshToken): string
    {
        $queryBuilder = $this->queryBuilderFactory->create()
            ->select('OXUSERID')
            ->from('oegraphqlrefreshtoken')
            ->where('TOKEN = :token')
            ->andWhere('EXPIRES_AT > NOW()')
            ->setParameter('token', $refreshToken);
        /** @var Result $result */
        $result = $queryBuilder->execute();

        $userId = (string)$result->fetchOne();

        if (!$userId) {
            throw new InvalidRefreshToken();
        }

        return $userId;
    }

    public function getTokenUser(string $refreshToken): UserInterface
    {
        $userId = $this->getTokenUserId($refreshToken);

        $userModel = $this->legacy->getUserModel($userId);
        $isAnonymous = !$userModel->getId();

        if ($isAnonymous) {
            $userModel->setId($userId);
        }

        return new UserDataType($userModel, $isAnonymous);
    }

    public function invalidateUserTokens(string $userId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create()
            ->update('oegraphqlrefreshtoken')
            ->where('OXUSERID = :userId')
            ->set('EXPIRES_AT', 'NOW()')
            ->setParameters([
                'userId' => $userId,
            ]);

        $queryBuilder->execute();
    }
}
