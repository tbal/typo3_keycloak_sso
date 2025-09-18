<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Keycloak SSO',
    'description' => 'TYPO3 Keycloak SSO extension by miniOrange enables seamless Single Sign-On for both TYPO3 backend and frontend users using their Keycloak credentials. Simplify the login process and enhance user experience by enabling admins and website visitors to access your Magento store using their existing Keycloak credentials. This extension is fully compatible with TYPO3 v13.',
    'author' => 'miniOrange',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.30-13.4.99',
        ],
    ],
    'version' => '2.0.2',
    'icon' => 'EXT:keycloak_sso/Resources/Public/Icons/Extension.svg',
    'state' => 'stable',
    'autoload' => [
        'psr-4' => [
            'Miniorange\\KeycloakSSO\\' => 'Classes/',
        ],
    ],
    'icon' => 'EXT:keycloak_sso/Resources/Public/Icons/Extension.png'
];
