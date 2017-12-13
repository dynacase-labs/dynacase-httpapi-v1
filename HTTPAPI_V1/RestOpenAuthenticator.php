<?php
namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\Api\Exception;

class RestOpenAuthenticator extends \OpenAuthenticator
{
    public static function getTokenId()
    {
        
        if (!empty($_GET[self::openGetId])) {
            return $_GET[self::openGetId];
        }
        
        $headers = apache_request_headers();
        if (!empty($headers["Authorization"])) {
            $hAuthorization=$headers["Authorization"];
        } elseif (!empty($headers["authorization"])) {
            $hAuthorization=$headers["authorization"];
        }
        if (!empty($hAuthorization)) {
            
            if (preg_match(sprintf("/%s\\s+(.*)$/", self::openAuthorizationScheme) , $hAuthorization, $reg)) {
                return trim($reg[1]);
            }
        }
        
        return "";
    }
    
    public static function verifyOpenAccess(\UserToken $token)
    {
        if ($token->type !== "REST") {
            return false;
        }
        $rawContext = $token->context;
        
        $allow = false;
        if ($rawContext === null) {
            return false;
        }
        
        if (empty($_SERVER["REDIRECT_URL"])) {
            return false;
        }
        $url = $_SERVER["REDIRECT_URL"];
        
        $relativeUrl = substr($url, strpos($url, "api/v1/") + strlen("api/v1"));
        $context = unserialize($rawContext);
        if (is_array($context)) {
            $allow = false;
            foreach ($context as $k => $rules) {
                if (is_array($rules)) {
                    if (!empty($rules["route"])) {
                        $route = $rules["route"];
                        
                        if (preg_match($route, $relativeUrl)) {
                            $allow = true;
                            break;
                        }
                    }
                } else {
                    // Simple route
                    if (preg_match("/^(GET|POST|PUT|DELETE)\\s+(.*)/", $rules, $reg)) {
                        $method = $reg[1];
                        $rules = $reg[2];
                    } else {
                        $method = "";
                    }
                    
                    $match = @preg_match($rules, $relativeUrl);
                    if ($match === false) {
                        $errors = error_get_last();
                        if (!empty($errors["message"])) {
                            $errors = $errors["message"];
                        };
                        
                        throw new Exception(print_r($errors, true));
                    }
                    
                    if ($match) {
                        
                        if (static::controlMethod($method)) {
                            $allow = true;
                            break;
                        }
                    }
                }
            }
        }
        
        return $allow;
    }
    
    protected static function controlMethod($method)
    {
        if ($method) {
            $requestMethod = \Dcp\HttpApi\V1\Api\Router::convertActionToCrud();
            switch ($requestMethod) {
                case \Dcp\HttpApi\V1\Crud\Crud::READ:
                    return ($method === "GET");
                case \Dcp\HttpApi\V1\Crud\Crud::UPDATE:
                    return ($method === "PUT");
                case \Dcp\HttpApi\V1\Crud\Crud::DELETE:
                    return ($method === "DELETE");
                case \Dcp\HttpApi\V1\Crud\Crud::CREATE:
                    return ($method === "POST");
                default;
                return false;
        }
    } else {
        return true;
    }
}
}
