<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Base\Framework;

use Exception;
use GraphQL\Upload\UploadMiddleware;
use Laminas\Diactoros\ServerRequestFactory;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\GraphQL\Base\Exception\UnableToParseToken;
use OxidEsales\GraphQL\Base\Service\JwtConfigurationBuilder;
use OxidEsales\GraphQL\Base\Service\TokenValidator;

use function apache_request_headers;
use function array_change_key_case;
use function file_get_contents;
use function json_decode;
use function sscanf;
use function trim;

class RequestReader
{
    public function __construct(
        private readonly TokenValidator $tokenValidator,
        private readonly JwtConfigurationBuilder $jwtConfigBuilder
    ) {
    }

    /**
     * Returns the encoded token from the authorization header
     *
     * @throws UnableToParseToken
     */
    public function getAuthToken(): ?UnencryptedToken
    {
        $authHeader = $this->getAuthorizationHeader();

        if ($authHeader === null) {
            return null;
        }

        // @phpstan-ignore-next-line
        [$jwt] = sscanf($authHeader, 'Bearer %s');

        if (!$jwt) {
            return null;
        }

        /** @var Configuration $jwtConfig */
        $jwtConfig = $this->jwtConfigBuilder->getConfiguration();

        try {
            /** @var UnencryptedToken $token */
            $token = $jwtConfig->parser()->parse($jwt);
        } catch (Exception) {
            throw new UnableToParseToken();
        }

        $this->tokenValidator->validateToken($token);

        return $token;
    }

    /**
     * Get the Request data
     *
     * @return array{query: string, variables: string[], operationName: string}
     */
    public function getGraphQLRequestData(string $inputFile = 'php://input'): array
    {
        $data = $this->getData($inputFile);

        return [
            'query' => $data['query'] ?? null,
            'variables' => $data['variables'] ?? null,
            'operationName' => $data['operationName'] ?? null,
        ];
    }

    private function getData(string $inputFile): array
    {
        if ($this->isContentType('application/json')) {
            return $this->getJsonData($inputFile);
        } elseif ($this->isContentType('multipart/form-data')) {
            return $this->getFormData();
        }
        return $this->getGenericData();
    }

    private function isContentType(string $contentType): bool
    {
        return isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], $contentType);
    }

    private function getJsonData(string $inputFile): array
    {
        $raw = file_get_contents($inputFile) ?: '';
        return json_decode($raw, true) ?: [];
    }

    private function getFormData(): array
    {
        $request = ServerRequestFactory::fromGlobals();
        $uploadMiddleware = new UploadMiddleware();
        $request = $uploadMiddleware->processRequest($request);
        $data = $request->getParsedBody();

        if (is_array($data) && !isset($data['operationName']) && isset($data['operation'])) {
            $data['operationName'] = $data['operation'];
        }

        return (array)$data;
    }

    private function getGenericData(): array
    {
        $data = $_REQUEST;

        if (isset($data['variables'])) {
            $data['variables'] = json_decode($data['variables'], true);
        }

        return $data;
    }

    /**
     * Get HTTP-Authorization header
     *
     * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
     * For this workaround to work, add these lines to your .htaccess file:
     * RewriteCond %{HTTP:Authorization} ^(.+)$
     * RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
     */
    private function getAuthorizationHeader(): ?string
    {
        $value = $this->getRegularHeaderValue();
        if ($value) {
            return $value;
        }

        if ($authCookie = $this->getAuthCookie()) {
            return 'Bearer ' . $authCookie;
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();

            if (is_array($headers)) {
                $headers = array_change_key_case($headers, CASE_LOWER);

                if (isset($headers['authorization'])) {
                    return trim($headers['authorization']);
                }
            }
        }

        return null;
    }

    private function getAuthCookie(): ?string
    {
        return (string) Registry::getUtilsServer()->getOxCookie('oxapi_jwt');
    }

    private function getRegularHeaderValue(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        return null;
    }
}
