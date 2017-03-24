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
            throw new Exception(sprintf("Invalid authtype '%s'", $authtype));
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
                $authClass = strtolower($authtype) . "Authenticator";
                if (!\Dcp\Autoloader::classExists($authClass)) {
                    throw new Exception("API0100", $authtype);
                }
                $auth = new $authClass($authtype, $provider);
        }
        
        return $auth;
    }
    /**
     * @param \Account      $userAccount account identify use for the token
     * @param array         $routes list of routes matches
     * @param int|\DateTime $expiration if it is a number, is use as a delay in seconds, if it is a DateTime object use as end validity date
     * @param bool          $oneshot if true the token can be used only on time (it is destroyed after use)
     * @param string        $description text description
     *
     * @return string   return the token identifier
     * @throws Exception
     */
    public static function getAuthorizationToken(\Account $userAccount, array $routes, $expiration = - 1, $oneshot = false, $description = "")
    {
        if ($expiration === - 1) {
            $expiration = \UserToken::INFINITY;
        }
        
        if (count($routes) === 0) {
            throw new Exception("API0105");
        }
        
        foreach ($routes as $k => $rules) {
            if (is_array($rules)) {
                if (empty($rules["route"])) {
                    throw new Exception("API0101", $k);
                }
                
                $methods = $rules["methods"];
                $queries = $rules["query"];
                $route = $rules["route"];
            } else {
                // Simple route
                if (preg_match("/^(GET|POST|PUT|DELETE)\\s+(.*)/", $rules, $reg)) {
                    $method = $reg[1];
                    $rules = $reg[2];
                } else {
                    $method = "*";
                }
                
                $methods = [$method];
                $queries = [];
                $route = $rules;
            }
            
            $apiv1 = preg_quote("api/v1/", $route[0]);
            
            if (strlen($route) < 2) {
                throw new Exception("API0102", $route);
            }
            $pattern = sprintf("%s%s%s", $route[0], $apiv1, substr($route, 1));
            
            $match = @preg_match($pattern, '');
            if ($match === false) {
                $errors = error_get_last();
                if (!empty($errors["message"])) {
                    $errors = $errors["message"];
                };
                
                throw new Exception("API0103", $k + 1, print_r($errors, true));
            }
            $routes[$k] = ["route" => $route, "methods" => $methods, "query" => $queries];
        }
        
        $scontext = serialize($routes);
        
        if (!$userAccount->isAffected()) {
            throw new Exception("API0106");
        }
        // create one
        $uk = new \UserToken("");
        $uk->userid = $userAccount->id;
        $uk->token = $uk->genToken();
        if (is_a($expiration, "\\DateTime")) {
            /**
             * @var \DateTime $expiration
             */
            $uk->expire = $expiration->format("Y-m-d H:i:s");
        } else {
            $uk->expire = $uk->setExpiration($expiration);
        }
        $uk->expendable = $oneshot;
        $uk->type = "REST";
        $uk->context = $scontext;
        $uk->description = $description;
        $err = $uk->add();
        if ($err) {
            throw new Exception("API0104", $err);
        }
        $token = $uk->token;
        
        return $token;
    }
}

