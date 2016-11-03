<?php
function restTokenMethod(Action & $action)
{
    $usage = new ActionUsage($action);
    //$search = $usage->addOptionalParameter("search", "search filters");
    $method = $usage->addRequiredParameter("method", "method to use", array(
        "delete",
        "create","routes"
    ));
    
    $usage->setStrictMode(false);
    $err = "";
    $message = $info = "";
    $token = "";
    switch ($method) {
        case "delete":
            $token = $usage->addRequiredParameter("token", "token");
            $usage->verify();
            $userToken = new UserToken($action->dbaccess, $token);
            if ($userToken->isAffected()) {
                $err = $userToken->delete();
                $message = sprintf(___("<p>Token <b>%s</b></br> has been deleted.</p>", "access") , $token);
            } else {
                $err = sprintf(___("Token %s not exists", "access") , $token);
            }
            
            break;

        case "create":
            $userLogin = $usage->addRequiredParameter("user", "User login");
            $methods = $usage->addRequiredParameter("methods", "HTTP methods", function ($v, $n, $usage)
            {
                return ApiUsage::isArray($v, $n, $usage);
            });
            $routes = $usage->addRequiredParameter("routes", "Route url regexp", function ($v, $n, $usage)
            {
                return ApiUsage::isArray($v, $n, $usage);
            });
            $queries = $usage->addRequiredParameter("queries", "Query args", function ($v, $n, $usage)
            {
                return ApiUsage::isArray($v, $n, $usage);
            });
            $expandable = $usage->addRequiredParameter("expandable", "expandable", array(
                "one",
                "always"
            ));
            $expireDate = $usage->addOptionalParameter("expiredate", "Selected action");
            $expireTime = $usage->addOptionalParameter("expiretime", "Selected action");
            $description = $usage->addOptionalParameter("description", "Text description");
            $expireInfinity = $usage->addOptionalParameter("expireinfinite", "Infinity", null, array(
                "true",
                "false"
            ));
            
            $usage->verify();
            $user = new Account($action->dbaccess);
            $user->setLoginName($userLogin);
            if (!$user->isAffected() || $user->accounttype !== "U") {
                $err = sprintf(___("User %s not exists", "access") , $userLogin);
            } else {
                
                if ($expireInfinity === "true") {
                    $expire = - 1;
                } else {
                    
                    $expireDateTime = sprintf("%sT%s", $expireDate, $expireTime);
                    if (!token_validateDate($expireDateTime)) {
                        $err = sprintf(___("Invalid date \"%s\" for token", "accessToken") , $expireDateTime);
                    }
                    $expire = new \DateTime($expireDateTime);
                }
                if (!$err) {
                    
                    $context = [];

                    try {
                    foreach ($routes as $k => $route) {
                        $query = $queries[$k];
                        $args = [];
                        if ($query) {
                            if ($query[0] !== '?') {
                                throw new \Dcp\Exception(sprintf("Invalid query part (must begin with ?): %s", $query));
                            }
                            if (strlen($query) > 1) {
                                if (preg_match_all(
                                    "/([^=]+)=([^&]+)/", $query, $matches
                                )) {
                                    foreach ($matches[1] as $km => $match) {
                                        $akey = substr($match, 1);
                                        $avalue = urldecode($matches[2][$km]);
                                        $args[$akey] = $avalue;
                                    }
                                }
                                if (empty($args)) {
                                    throw new \Dcp\Exception(
                                        sprintf(
                                            "Invalid query part (no arguments find): %s",
                                            $query
                                        )
                                    );
                                }
                            }
                        }
                        
                        if (!empty($route)) {
                            $context[] = ["methods" => explode(",", $methods[$k]) , "route" => $route, "query" => $args];
                        }
                    }

                        $userTokenId = Dcp\HttpApi\V1\AuthenticatorManager::getAuthorizationToken($user, $context, $expire, ($expandable === "one") , $description);
                        $message = sprintf(___("<p>Token <b>%s</b></br> has been create.</p>", "access") , $userTokenId);
                        $token = $userTokenId;
                    }
                    catch(\Exception $e) {
                        $err = $e->getMessage();
                    }
                }
            }
            
            break;

    case "routes":

        $usage->verify();
        $info = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "CRUD_CLASS"), true);
        usort($info, function ($a, $b) {
            return strcmp($a["canonicalURL"], $b["canonicalURL"]);
        });
        $message=___("Api Route List", "resttoken");
    break;
    }
    
    header('Content-Type: application/json');
    
    if ($err) {
        header("HTTP/1.0 400 Error");
        $response = ["success" => false, "error" => $err];
    } else {
        $response = ["success" => true, "token" => $token, "message" => $message, "info"=>$info];
    }
    $action->lay->noparse = true;
    $action->lay->template = json_encode($response);
}

function token_validateDate($date)
{
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $date, $parts) > 0) {
        $time = mktime($parts[4], $parts[5], 0, $parts[2], $parts[3], $parts[1]);
        
        $input_time = strtotime($date);
        if ($input_time === false) return false;
        
        return $input_time == $time;
    } else {
        return false;
    }
}
