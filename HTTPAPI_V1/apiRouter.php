<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

chdir('..'); // need to be in root directory to be authenticated
require_once ('WHAT/autoload.php');
require_once ('WHAT/Lib.Main.php');

//region initErrorHandling
ini_set("display_error", "off");
function jsonFatalShutdown()
{
    $error = error_get_last();
    if ($error !== NULL) {
        if ($error["type"] == E_ERROR) {
            ob_clean();
            
            $return = new Dcp\HttpApi\V1\RecordReturn();
            $return->setHttpStatusCode(500, "Dynacase Fatal Error");
            $message = new Dcp\HttpApi\V1\RecordReturnMessage();
            $message->contentText = join(" ", $error);
            $message->type = $message::ERROR;
            $return->addMessage($message);
            $return->success=false;
            $return->send();
        }
    }
}

register_shutdown_function('jsonFatalShutdown');
//endregion initErrorHandling

//region Authentification
if (file_exists('maintenance.lock')) {
    
    $return = new Dcp\HttpApi\V1\RecordReturn();
    $return->setHttpStatusCode(503, "Service Unavailable");
    $message = new Dcp\HttpApi\V1\RecordReturnMessage();
    $message->contentText = _("maintenance in progress");
    $message->type = $message::ERROR;
    $return->addMessage($message);
    $return->send();
    
    exit();
}

$authtype = getAuthType();

if ($authtype == 'apache') {
    // Apache has already handled the authentication
    global $_SERVER;
    if ($_SERVER['PHP_AUTH_USER'] == "") {
        $return = new Dcp\HttpApi\V1\RecordReturn();
        $return->setHttpStatusCode(403, "Forbidden");
        $message = new Dcp\HttpApi\V1\RecordReturnMessage();
        $message->contentText = _("User must be authenticate");
        $message->type = $message::ERROR;
        $return->addMessage($message);
        $return->send();
        
        exit();
    }
} else {
    
    $status = AuthenticatorManager::checkAccess(null, true);
    switch ($status) {
        case 0: // it'good, user is authentified
            break;

        case -1:
            // User must change his password
            // $action->session->close();
            $o["error"] = _("not authenticated:ERRNO_BUG_639");
            print json_encode($o);
            exit(0);
            break;

        default:
            
            $auth = AuthenticatorManager::$auth;
            if ($auth === false) {
                throw new \Dcp\Exception("Could not get authenticator.");
            }
    }
    $_SERVER['PHP_AUTH_USER'] = AuthenticatorManager::$auth->getAuthUser();
}
// First control
if (empty($_SERVER['PHP_AUTH_USER'])) {
    $return = new Dcp\HttpApi\V1\RecordReturn();
    $return->setHttpStatusCode(403, "Forbidden");
    $return->success = false;
    $message = new Dcp\HttpApi\V1\RecordReturnMessage();
    $message->contentText = _("User must be authenticated");
    $message->type = $message::ERROR;
    $return->addMessage($message);
    $return->send();
    exit();
}
//endregion Authentification
//Initialize return object
$return = new Dcp\HttpApi\V1\RecordReturn();
try {
    global $action;
    WhatInitialisation(AuthenticatorManager::$session);
    $action->name = "HTTPAPI_V1";
    setSystemLogin($_SERVER['PHP_AUTH_USER']);
    $messages = array();
    //Routing
    $data = Dcp\HttpApi\V1\apiRouterV1::execute($messages);

    $return->setData($data);
    foreach ($messages as $message) {
        $return->addMessage($message);
    }
    // Handle DCP warning message
    $warnings = $action->parent->getWarningMsg();
    foreach ($warnings as $warning) {
        $message = new Dcp\HttpApi\V1\RecordReturnMessage();
        $message->contentText = $warning;
        $message->type = $message::WARNING;
        $return->addMessage($message);
    }
    $action->parent->clearWarningMsg();
    // Handle DCP log message
    $warnings = $action->parent->getLogMsg();
    foreach ($warnings as $warning) {
        $message = new Dcp\HttpApi\V1\RecordReturnMessage();
        $message->contentText = $warning;
        $message->type = $message::NOTICE;
        $return->addMessage($message);
    }
    $action->parent->clearLogMsg();
}
//region ErrorCatching
catch(\Dcp\HttpApi\V1\Exception $exception) {

    $return->setHttpStatusCode($exception->getHttpStatus() , $exception->getHttpMessage());
    $return->exceptionMessage = $exception->getDcpMessage();
    $return->success = false;
    $message = new Dcp\HttpApi\V1\RecordReturnMessage();
    $message->contentText = $exception->getDcpMessage();
    $message->contentText = $exception->getUserMessage();
    if (!$message->contentText) {
        $message->contentText = $exception->getDcpMessage();
    }
    $message->type = $message::ERROR;
    $message->code = $exception->getDcpCode();
    $message->data = $exception->getData();
    $message->uri = $exception->getURI();
    $return->setHeaders($exception->getHeaders());

    $return->addMessage($message);
}
catch(\Dcp\Exception $exception) {
    $return = new Dcp\HttpApi\V1\RecordReturn();
    $return->setHttpStatusCode(400, "Dcp Exception");
    $return->success = false;
    $message = new Dcp\HttpApi\V1\RecordReturnMessage();
    $message->contentText = $exception->getDcpMessage();
    $message->type = $message::ERROR;
    $message->code = $exception->getDcpCode();
    $return->addMessage($message);
}
catch(\Exception $exception) {
    $return = new Dcp\HttpApi\V1\RecordReturn();
    $return->setHttpStatusCode(400, "Exception");
    $return->success = false;
    $message = new Dcp\HttpApi\V1\RecordReturnMessage();
    $message->contentText = $exception->getMessage();
    $message->type = $message::ERROR;
    $message->code = "API0001";
    $return->addMessage($message);
}
//endregion ErrorCatching
//Send the HTTP return
$return->send();

