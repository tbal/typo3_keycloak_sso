<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;

call_user_func(
    function () {
        $version = new Typo3Version();
        if (version_compare($version, '10.0.0', '>=')) {
            $extensionName = 'keycloak_sso';
            $cache_actions_beoidc = [Miniorange\KeycloakSSO\Controller\BeoidcController::class => 'request'];
        } else {
            $extensionName = 'Miniorange.keycloak_sso';
            $cache_actions_beoidc = ['Beoidc' => 'request'];
        }

    }
);
