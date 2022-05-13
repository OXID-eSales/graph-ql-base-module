<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Infrastructure;

use DateTimeImmutable;
use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Base\DataType\User as UserDataType;
use OxidEsales\GraphQL\Base\Service\Token as TokenService;
use PDO;

class Token
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    public function registerToken(UnencryptedToken $token, DateTimeImmutable $time, DateTimeImmutable $expire): void
    {
        $storage = oxNew(Model\Token::class);
        $storage->assign(
            [
                'OXID' => $token->claims()->get(TokenService::CLAIM_TOKENID),
                'OXSHOPID' => $token->claims()->get(TokenService::CLAIM_SHOPID),
                'OXUSERID' => $token->claims()->get(TokenService::CLAIM_USERID),
                'ISSUED_AT' => $time->format('Y-m-d H:i:s'),
                'EXPIRES_AT' => $expire->format('Y-m-d H:i:s'),
                'USERAGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'TOKEN' => $token->toString(),
            ]
        );
        $storage->save();
    }

    public function isTokenRegistered(string $tokenId): bool
    {
        $storage = oxNew(Model\Token::class);
        $storage->load($tokenId);

        return $storage->isLoaded();
    }

    public function removeExpiredTokens(UserDataType $user): void
    {
        $queryBuilder = $this->queryBuilderFactory->create()
            ->delete('oegraphqltoken')
            ->where('OXUSERID = :userId')
            ->andWhere('EXPIRES_AT <= NOW()')
            ->setParameters([
                'userId' => (string)$user->id(),
            ]);

        $queryBuilder->execute();
    }

    public function canIssueToken(UserDataType $user, int $quota): bool
    {
        $return = false;

        $result = $this->queryBuilderFactory->create()
            ->select('count(oegraphqltoken.oxid) as counted')
            ->from('oegraphqltoken')
            ->where('OXUSERID = :userId')
            ->setParameters([
                'userId' => (string)$user->id(),
            ])
            ->execute();

        if (is_object($result)) {
            $return = (int)$result->fetch(PDO::FETCH_ASSOC)['counted'] < $quota;
        }

        return $return;
    }

    public function tokenDelete(?UserDataType $user = null, ?string $tokenId = null, ?int $shopId = null): int
    {
        $parameters = [];
        $condition = 'where';

        $queryBuilder = $this->queryBuilderFactory->create()
            ->delete('oegraphqltoken');

        if ($tokenId) {
            $queryBuilder->$condition('OXID = :tokenId');
            $parameters['tokenId'] = $tokenId;
            $condition = 'andWhere';
        }

        if ($user) {
            $queryBuilder->$condition('OXUSERID = :userId');
            $parameters['userId'] = (string)$user->id();
        }

        if ($shopId) {
            $queryBuilder->$condition('OXSHOPID = :shopId');
            $parameters['shopId'] = $shopId;
        }

        $queryBuilder->setParameters($parameters);

        $result = $queryBuilder->execute();

        return is_object($result) ? $result->columnCount() : (int)$result;
    }

    public function userHasToken(UserDataType $user, string $tokenId): bool
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder
            ->select('count(OXID)')
            ->from('oegraphqltoken')
            ->where('OXID = :tokenId')
            ->andWhere('OXUSERID = :userId')
            ->setParameters([
                'tokenId' => $tokenId,
                'userId' => (string)$user->id(),
            ]);

        $result = $queryBuilder->execute();

        if (is_object($result)) {
            return $result->fetch(PDO::FETCH_COLUMN) > 0;
        }

        return false;
    }
}
