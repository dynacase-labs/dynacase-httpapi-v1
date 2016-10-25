<?php
namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\Api\Exception;

class AuthenticatorManager extends \AuthenticatorManager
{
    
    protected static $authType;
    

    protected static function getAuthenticatorClass($authtype = null, $provider = \Authenticator::nullProvider)
    {
        if (!$authtype) {
            $authtype = self::getAuthType();
        }
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $authtype)) {
            throw new \Dcp\Exception(sprintf("Invalid authtype '%s'", $authtype));
        }
        
        $auth = null;
        switch ($authtype) {
            case "html":
                $auth = new \htmlAuthenticator($authtype, $provider);
                break;

            case "open":
                $auth = new RestOpenAuthenticator($authtype, $provider);
                break;

            case "basic":
                $auth = new \basicAuthenticator($authtype, $provider);
                break;

            default:
                throw new \Dcp\Exception(sprintf("Cannot find authenticator '%s'", $authtype));
        }
        
        return $auth;
    }
    
    public static function getAuthorizationToken(\Account $userAccount, array $routes, $expireDelay = - 1, $oneshot = false, $description = "")
    {
        if ($expireDelay === - 1) {
            $expireDelay = \UserToken::INFINITY;
        }
        
        if (count($routes) === 0) {
            throw new Exception("No route given");
        }
        
        $scontext = serialize($routes);
        
        if (!$userAccount->isAffected()) {
            throw new Exception("User is not valid");
        }
        // create one
        $uk = new \UserToken("");
        $uk->userid = $userAccount->id;
        $uk->token = $uk->genToken();
        $uk->expire = $uk->setExpiration($expireDelay);
        $uk->expendable = $oneshot;
        $uk->type = "REST";
        $uk->context = $scontext;
        $uk->description = $description;
        $err = $uk->add();
        if ($err) {
            throw new Exception($err);
        }
        $token = $uk->token;
        
        return $token;
    }
}

