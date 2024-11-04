<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 */
$aModule = [
    'id'          => 'oe_graphql_base',
    'title'       => [
        'de'      => 'GraphQL Base',
        'en'      => 'GraphQL Base',
    ],
    'description' => [
        'de'      => '<span>OXID GraphQL API Framework</span>',
        'en'      => '<span>OXID GraphQL API Framework</span>',
    ],
    'thumbnail'   => 'logo.png',
    'version'     => '10.0.0-rc.1',
    'author'      => 'OXID eSales',
    'url'         => 'www.oxid-esales.com',
    'email'       => 'info@oxid-esales.com',
    'extend'      => [
    ],
    'controllers' => [
        // Widget Controller
        'graphql' => OxidEsales\GraphQL\Base\Component\Widget\GraphQL::class,
    ],
    'templates'   => [
    ],
    'blocks'      => [
    ],
    'settings'    => [
        [
            'group' => 'graphql_base',
            'name'  => 'sJsonWebTokenSignature',
            'type'  => 'str',
            'value' => 'CHANGE ME',
        ],
        [
            'group' => 'graphql_base',
            'name'  => 'sRefreshTokenLifetime',
            'type'        => 'select',
            'constraints' => '24hrs|7days|30days|60days|90days',
            'value' => '24hrs'
        ],
        [
            'group' => 'graphql_base',
            'name'  => 'sJsonWebTokenLifetime',
            'type'        => 'select',
            'constraints' => '1min|5min|10min|15min|1hrs|3hrs|8hrs|24hrs',
            'value' => '8hrs'
        ],
        [
            'group' => 'graphql_base',
            'name'  => 'sJsonWebTokenUserQuota',
            'type'  => 'num',
            'value' => 10000,
        ],
        [
            'group' => 'graphql_base',
            'name'  => 'sFingerprintCookieMode',
            'type'        => 'select',
            'constraints' => 'sameOrigin|crossOrigin',
            'value' => 'sameOrigin'
        ],
    ],
    'events'      => [
        'onActivate'   => '\OxidEsales\GraphQL\Base\Infrastructure\ModuleSetup::onActivate',
        'onDeactivate' => '\OxidEsales\GraphQL\Base\Infrastructure\ModuleSetup::onDeactivate',
    ],
];
