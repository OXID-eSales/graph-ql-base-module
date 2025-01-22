<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Tests\Unit\Framework;

use Laminas\Diactoros\UploadedFile;
use GraphQL\Error\InvariantViolation;
use Lcobucci\JWT\Token;
use OxidEsales\GraphQL\Base\Exception\UnableToParseToken;
use OxidEsales\GraphQL\Base\Framework\RequestReader;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Service\TokenValidator;
use OxidEsales\GraphQL\Base\Tests\Unit\BaseTestCase;

class RequestReaderTest extends BaseTestCase
{
    // phpcs:disable
    protected static $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.cThIIoDvwdueQB468K5xDc5633seEFoqwxjF_xSJyQQ';

    public function testGetAuthTokenWithoutToken(): void
    {
        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );
        $this->assertNull($requestReader->getAuthToken());
    }

    public function testGetAuthTokenWithWrongFormattedHeader(): void
    {
        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );
        $headers = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
        ];

        foreach ($headers as $header) {
            $_SERVER[$header] = 'authtoken';
            $this->assertNull($requestReader->getAuthToken());

            unset($_SERVER[$header]);
        }
    }

    /**
     * @dataProvider headerNamesDataProvider
     *
     * @param mixed $headerName
     */
    public function testGetAuthTokenWithCorrectFormattedHeaderButInvalidJWT($headerName): void
    {
        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );
        $this->expectException(UnableToParseToken::class);

        $_SERVER[$headerName] = 'Bearer invalidjwt';
        $requestReader->getAuthToken();
    }

    public static function headerNamesDataProvider(): array
    {
        return [
            ['HTTP_AUTHORIZATION'],
            ['REDIRECT_HTTP_AUTHORIZATION'],
        ];
    }

    /**
     * @dataProvider headerNamesDataProvider
     *
     * @param mixed $headerName
     */
    public function testGetAuthTokenWithCorrectFormatCallsTokenValidation($headerName): void
    {
        $tokenValidator = $this->createPartialMock(TokenValidator::class, ['validateToken']);
        $tokenValidator->expects($this->once())->method('validateToken');

        $requestReader = new RequestReader(
            $tokenValidator,
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );

        // add also a whitespace to the beginning if the header
        // to test the trim() call
        $_SERVER[$headerName] = ' Bearer ' . self::$token;
        $token = $requestReader->getAuthToken();
        $this->assertInstanceOf(Token::class, $token);
        unset($_SERVER[$headerName]);
    }

    public function testGetGraphQLRequestDataWithEmptyRequest(): void
    {
        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );
        $this->assertEquals(
            [
                'query' => null,
                'variables' => null,
                'operationName' => null,
            ],
            $requestReader->getGraphQLRequestData()
        );
    }

    public function testGetGraphQLRequestDataWithInputRequest(): void
    {
        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $this->assertEquals(
            [
                'query' => 'query {token}',
                'variables' => null,
                'operationName' => null,
            ],
            $requestReader->getGraphQLRequestData(__DIR__ . '/fixtures/simpleRequest.json')
        );
        unset($_SERVER['CONTENT_TYPE']);
    }

    public function testGetGraphQLRequestDataWithInputRequestWithoutJson(): void
    {
        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );
        $_SERVER['CONTENT_TYPE'] = 'text/plain';
        $_REQUEST['query'] = 'query {token_}';
        $_REQUEST['variables'] = '{"foo":"bar"}';
        $_REQUEST['operationName'] = 'operation_name';
        $this->assertSame(
            [
                'query' => 'query {token_}',
                'variables' => ['foo' => 'bar'],
                'operationName' => 'operation_name',
            ],
            $requestReader->getGraphQLRequestData()
        );
        unset($_SERVER['CONTENT_TYPE'], $_REQUEST['query'], $_REQUEST['variables'], $_REQUEST['operationName']);
    }

    public function testGetGraphQLRequestDataWithInvalidFileInput(): void
    {
        $this->expectException(InvariantViolation::class);

        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );
        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundaryoaY0xvjC2DBjmPRZ';

        $requestReader->getGraphQLRequestData();

        unset($_SERVER['CONTENT_TYPE']);
    }

    public function testGetGraphQLRequestDataWithFileInput(): void
    {
        $requestReader = new RequestReader(
            $this->createPartialMock(TokenValidator::class, []),
            $this->getJwtConfigurationBuilder($this->getLegacyMock())
        );

        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundarycp0uqGswsYjCH7rC';
        $_POST['map'] = json_encode(['0' => ['variables.file']]);
        $_POST['operations'] = '{"query":"query anonymous {token}", "variables":{"file":null}, "operationName":"anonymous"}';

        $_FILES['0'] = [
            'name' => 'example.txt',
            'type' => 'text/plain',
            'tmp_name' => './fixtures/example.txt',
            'error' => 0,
            'size' => 18,
        ];

        $this->assertEquals(
            [
                'query' => 'query anonymous {token}',
                'variables' => [
                    'file' => new UploadedFile(
                        './fixtures/example.txt',
                        18,
                        0,
                        'example.txt',
                        'text/plain'
                    ),
                ],
                'operationName' => 'anonymous',
            ],
            $requestReader->getGraphQLRequestData()
        );
        unset($_SERVER['CONTENT_TYPE'], $_POST['map'], $_POST['operations']);
    }

    // phpcs:enable

    protected function getLegacyMock(): Legacy
    {
        $mock = $this->getMockBuilder(Legacy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects($this->any())
            ->method('getShopUrl')
            ->willReturn('www.myoxidshop.com');

        return $mock;
    }
}
