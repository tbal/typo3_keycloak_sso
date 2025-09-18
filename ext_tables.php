<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(
    function () {
    $version = GeneralUtility::makeInstance(Typo3Version::class);
    $isV13OrHigher = version_compare($version, '13.0.0', '>=');
    $extensionName = $isV13OrHigher || version_compare($version, '10.0.0', '>=') ? 'keycloak_sso' : 'Miniorange.keycloak_sso';
    $cache_actions_beoidc = $isV13OrHigher || version_compare($version, '10.0.0', '>=')? [Miniorange\KeycloakSSO\Controller\BeoidcController::class => 'request']: ['Beoidc' => 'request'];    if ($isV13OrHigher) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['keycloak_sso']['BeoidcModule'] = [
            'extensionName' => $extensionName,
            'mainModuleName' => 'tools',
            'subModuleName' => 'beoidckey',
            'controllerActions' => $cache_actions_beoidc,
            'access' => 'admin,user,group',
            'iconIdentifier' => 'keycloak_sso-plugin-bekey',
            'labels' => 'LLL:EXT:keycloak_sso/Resources/Private/Language/locallang_bekey.xlf',
            'position' => 'top',
        ];
        } else {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $extensionName,
            'tools', // Make module a submodule of 'tools'
            'beoidckey', // Submodule key
            '4', // Position
            $cache_actions_beoidc,
            [
                'access' => 'admin,user,group',
                'icon'   => 'EXT:keycloak_sso/Resources/Public/Icons/Extension.png',
                'labels' => 'LLL:EXT:keycloak_sso/Resources/Private/Language/locallang_bekey.xlf'
            ]
        );
    }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Feoidc',
            'LLL:EXT:keycloak_sso/Resources/Private/Language/locallang_db.xlf:tx_oauth_feoidc.name',
            'keycloak_sso-plugin-bekey'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'LLL:EXT:keycloak_sso/Resources/Private/Language/locallang_db.xlf:tx_oauth_response.name',
            'keycloak_sso-plugin-bekey'
        );

    }
);