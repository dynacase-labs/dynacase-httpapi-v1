<?php
namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\Api\Exception;

class RestOpenAuthenticator extends \OpenAuthenticator {
    public static function getTokenId() {


        if (!empty($_GET["dcp-authorization"])) {
            return $_GET["dcp-authorization"];
        }

        $headers = apache_request_headers();
        if (!empty($headers["Authorization"])) {

            if (preg_match("/Dynacase\s+(.*)$/i", $headers["Authorization"], $reg)){

                return trim($reg[1]);
            }
        }


        return "";
    }

    public static function verifyOpenAccess(\UserToken $token)
    {
        if ($token->type!=="REST") {
            return false;
        }
        $rawContext=$token->context;

        $allow = false;
        if ($rawContext === null) {
            return false;
        }

        if (empty($_SERVER["REDIRECT_URL"])) {
            return false;
        }
        $url=$_SERVER["REDIRECT_URL"];

            $context = unserialize($rawContext);
            if (is_array($context)) {
                $allow = false;
                foreach ($context as $k => $rule) {
                    if (is_array($rule)) {
                        print "TODO COMPLEX ROUTE";
                    } else {
                        // Simple route
                        $pattern=sprintf("%sapi/v1/%s", $rule[0], substr($rule, 1));

                        if (preg_match($pattern, $url)) {
                            $allow=true;
                            break;
                        }

                    }
                }
            }


        return $allow;
    }


}