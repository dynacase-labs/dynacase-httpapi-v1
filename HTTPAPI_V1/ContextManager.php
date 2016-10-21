<?php
namespace Dcp\HttpApi\V1;

class ContextManager
{
    /**
     * Control user has a good session
     * Complete AuthenticatorManager singleton
     * @throws Api\Exception
     */
    public static function controlAuthent()
    {
        $authtype = AuthenticatorManager::getAuthType();
        
        if ($authtype == 'apache') {
            // Apache has already handled the authentication
            global $_SERVER;
            if ($_SERVER['PHP_AUTH_USER'] == "") {
                $exception = new \Dcp\HttpApi\V1\Api\Exception("User must be authenticated");
                $exception->setHttpStatus("403", "Forbidden");
                throw $exception;
            }
        } else {
            // Ask authentification if HTML required
            $noAskAuthent = (preg_match("/\\.html$/", $_SERVER["REQUEST_URI"]) === 0);
            $status = AuthenticatorManager::checkAccess(null, $noAskAuthent);
            
            switch ($status) {
                case \Authenticator::AUTH_OK: // it'good, user is authentified
                    break;

                default:
                    $auth = AuthenticatorManager::$auth;
                    if ($auth === false) {
                        $exception = new \Dcp\HttpApi\V1\Api\Exception("Could not get authenticator");
                        $exception->setHttpStatus("500", "Could not get authenticator");
                        $exception->setUserMessage("Could not get authenticator");
                        throw $exception;
                    }
            }
            $_SERVER['PHP_AUTH_USER'] = AuthenticatorManager::$auth->getAuthUser();
        }
        // First control
        if (empty($_SERVER['PHP_AUTH_USER'])) {
            $exception = new \Dcp\HttpApi\V1\Api\Exception("User must be authenticated");
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
    }
    
    public static function initCoreApplication()
    {
        
        global $action;
        WhatInitialisation(AuthenticatorManager::$session);
        initMainVolatileParam($action->parent);
        $action->name = "HTTPAPI_V1";
        if (!empty($_SERVER['PHP_AUTH_USER'])) {
            setSystemLogin($_SERVER['PHP_AUTH_USER']);
        }
        return $action;
    }
    /**
     * @return \Action
     */
    public static function getCoreAction()
    {
        
        global $action;
        
        return $action;
    }
}
