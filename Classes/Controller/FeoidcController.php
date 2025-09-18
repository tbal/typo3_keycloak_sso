<?php

namespace Miniorange\KeycloakSSO\Controller;

use Miniorange\KeycloakSSO\Helper\Constants;
use Miniorange\KeycloakSSO\Helper\MoUtilities;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * FeoidcController
 */
class FeoidcController extends ActionController
{

    /**
     * requestAction
     * @return void
     */
    public function requestAction(): ResponseInterface
    {
        error_log("Feoidc Controller, inside printAction: ");

    	// Handle Test Configuration
    	if (isset($_REQUEST['RelayState']) && $_REQUEST['RelayState'] === 'testconfig') {
        	if (session_id() == '' || !isset($_SESSION)) {
            	session_start();
        	}
        	$_SESSION['mo_keycloak_sso_test'] = true;
        	return $this->redirectAction();
    	}

	// Handle actual SSO login (with ?app=XYZ or default app)
	if (isset($_REQUEST['app']) && !empty($_REQUEST['app'])) {
	        if (session_id() == '' || !isset($_SESSION)) {
	            session_start();
	        }
	        $_SESSION['mo_oauth_app'] = $_REQUEST['app'];   // store app name if needed
	        return $this->redirectAction();
    	}

    	// Enhanced fallback logic: If no app parameter, try to get default configuration
    	if (!isset($_REQUEST['app']) || empty($_REQUEST['app'])) {
    	    if (session_id() == '' || !isset($_SESSION)) {
    	        session_start();
    	    }
    	    
    	    // Try to get default OIDC configuration from database
    	    $json_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
    	    $app = json_decode($json_object, true);
    	    
    	    if ($app && isset($app[Constants::OIDC_APP_NAME]) && !empty($app[Constants::OIDC_APP_NAME])) {
    	        // Use the configured app_name from database
    	        $_SESSION['mo_oauth_app'] = $app[Constants::OIDC_APP_NAME];
    	        return $this->redirectAction();
    	    } else {
    	        // No valid configuration found
    	        $error = "SSO configuration error: No valid OIDC configuration found. Please check your settings.";
    	        return $this->createErrorResponse($error);
    	    }
    	}

    	// Check if SSO is configured and provide data to template
    	$ssoEnabled = $this->isSsoConfigured();
    	$error = null;
    	
    	if (!$ssoEnabled) {
    	    $error = "SSO is not properly configured. Please check your OAuth/OpenID Connect settings.";
    	}

    	// Pass data to template
    	$this->view->assign('ssoEnabled', $ssoEnabled);
    	$this->view->assign('error', $error);

    	// Render /feoidc page
    	$responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
    	$streamFactory = GeneralUtility::makeInstance(\Psr\Http\Message\StreamFactoryInterface::class);

    	try {
	        $renderedContent = $this->view->render();
	        return $responseFactory->createResponse()
	            ->withHeader('Content-Type', 'text/html; charset=utf-8')
	            ->withBody($streamFactory->createStream($renderedContent));
    	} catch (\Exception $e) {
	        error_log('Error rendering view in FeoidcController: ' . $e->getMessage());
	        return $responseFactory->createResponse()
	            ->withHeader('Content-Type', 'text/html; charset=utf-8')
	            ->withBody($streamFactory->createStream('Error rendering view'));
    	}
    }

    /**
     * Redirect to Authorization URL
     */
    public function redirectAction(): ResponseInterface
    {
        $json_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
        $app = json_decode($json_object, true);
        
        if (!$app || !isset($app[Constants::OIDC_APP_NAME]) || empty($app[Constants::OIDC_APP_NAME])) {
            error_log('SSO Error: Application configuration is missing or incomplete.');
            return $this->createErrorResponse('SSO configuration error: Application not properly configured.');
        }
        
        // Additional validation: Check if all required OIDC fields are present
        $requiredFields = [
            Constants::OIDC_CLIENT_ID,
            Constants::OIDC_CLIENT_SECRET,
            Constants::OIDC_AUTH_URL,
            Constants::OIDC_REDIRECT_URL,
            Constants::OIDC_SCOPE
        ];
        
        foreach ($requiredFields as $field) {
            if (!isset($app[$field]) || empty($app[$field])) {
                error_log('SSO Error: Missing required field: ' . $field);
                return $this->createErrorResponse('SSO configuration error: Missing required field ' . $field . '. Please check your settings.');
            }
        }
        
        $state = base64_encode($app[Constants::OIDC_APP_NAME]);
        $authorizationUrl = $app[Constants::OIDC_AUTH_URL];

        if (strpos($authorizationUrl, "google") !== false) {
            $authorizationUrl = "https://accounts.google.com/o/oauth2/auth";
        }

        $authorizationUrl .= (strpos($authorizationUrl, '?') !== false ? "&" : "?")
            . "client_id=" . $app[Constants::OIDC_CLIENT_ID]
            . "&scope=" . $app[Constants::OIDC_SCOPE]
            . "&redirect_uri=" . $app[Constants::OIDC_REDIRECT_URL]
            . "&response_type=code&state=" . $state;

        if (session_id() == '' || !isset($_SESSION))
            session_start();
        $_SESSION['oauth2state'] = $state;
        $_SESSION['appname'] = $app[Constants::OIDC_APP_NAME];


        $version = new Typo3Version();
        $typo3Version = $version->getVersion();

        if ($typo3Version >= 12) {
            $responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
            return $responseFactory->createResponse()
                ->withAddedHeader('Location', $authorizationUrl)
                ->withStatus(302);
        } else {
            header('Location: ' . $authorizationUrl);
            $responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
            return $responseFactory->createResponse(302, 'Redirecting to authorization URL');
        }

    }

    /**
     * Check if SSO is properly configured
     * 
     * @return bool True if SSO is configured, false otherwise
     */
    private function isSsoConfigured(): bool
    {
        try {
            $json_object = MoUtilities::fetchFromDb(Constants::OIDC_OIDC_OBJECT, Constants::TABLE_OIDC);
            if (empty($json_object)) {
                return false;
            }
            
            $app = json_decode($json_object, true);
            if (!$app || !is_array($app)) {
                return false;
            }
            
            // Check if required fields are present and not empty
            $requiredFields = [
                Constants::OIDC_APP_NAME,
                Constants::OIDC_CLIENT_ID,
                Constants::OIDC_CLIENT_SECRET,
                Constants::OIDC_AUTH_URL,
                Constants::OIDC_REDIRECT_URL,
                Constants::OIDC_SCOPE
            ];
            
            foreach ($requiredFields as $field) {
                if (!isset($app[$field]) || empty($app[$field])) {
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Error checking SSO configuration: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create an error response
     * 
     * @param string $message Error message
     * @return ResponseInterface Error response
     */
    private function createErrorResponse(string $message): ResponseInterface
    {
        $responseFactory = GeneralUtility::makeInstance(ResponseFactoryInterface::class);
        $streamFactory = GeneralUtility::makeInstance(\Psr\Http\Message\StreamFactoryInterface::class);
        
        $errorHtml = '<!DOCTYPE html>
<html>
<head>
    <title>SSO Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .error { color: #d32f2f; background: #ffebee; padding: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="error">
        <h2>SSO Authentication Error</h2>
        <p>' . htmlspecialchars($message) . '</p>
        <p>Please contact your administrator for assistance.</p>
    </div>
</body>
</html>';
        
        return $responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($streamFactory->createStream($errorHtml));
    }

}
