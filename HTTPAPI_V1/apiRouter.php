<?php
/*
 * @author Anakeen
 * @package FDL
*/
chdir('..'); // need to be in root directory to be authenticated
require_once ('WHAT/autoload.php');
require_once ('WHAT/Lib.Main.php');

$tracing = \ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "ACTIVATE_TRACE");
\Dcp\ConsoleTime::activate($tracing === "TRUE");
\Dcp\ConsoleTime::begin();
//region initErrorHandling
ini_set("display_error", "off");
$loggers = array();
$jsonFatalShutdown = function () use (&$loggers)
{
    $error = error_get_last();
    if ($error !== NULL) {
        if (in_array($error["type"], array(
            E_ERROR,
            E_PARSE,
            E_COMPILE_ERROR,
            E_CORE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR
        ))) {
            ob_clean();
            $return = new \Dcp\HttpApi\V1\Api\RecordReturn();
            $return->setHttpStatusCode(500, "Dynacase Fatal Error");
            $message = new \Dcp\HttpApi\V1\Api\RecordReturnMessage();
            $message->contentText = join(", ", $error);
            $message->type = $message::ERROR;
            $return->addMessage($message);
            $return->success = false;
            $return->send();
            foreach ($loggers as $currentLogger) {
                /* @var \Dcp\HttpApi\V1\Logger\Logger $currentLogger */
                $currentLogger->writeError("PHP Error : " . $message->contentText);
            }
        }
    }
};

\Dcp\ConsoleTime::step("Include");
register_shutdown_function($jsonFatalShutdown);
//endregion initErrorHandling
$return = new Dcp\HttpApi\V1\Api\RecordReturn();
//region initLogger
try {
    $loggerList = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "SYSTEM_LOGGER") , true);
    $customLogger = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "CUSTOM_LOGGER") , true);
    foreach ($loggerList as $currentLogger) {
        $loggers[] = new $currentLogger();
    }
    $writeError = function ($message, $context = null, $stack = null) use (&$loggers)
    {
        foreach ($loggers as $currentLogger) {
            /* @var \Dcp\HttpApi\V1\Logger\Logger $currentLogger */
            $currentLogger->writeError($message, $context, $stack);
        }
    };
    $writeWarning = function ($message, $context = null, $stack = null) use (&$loggers)
    {
        foreach ($loggers as $currentLogger) {
            /* @var \Dcp\HttpApi\V1\Logger\Logger $currentLogger */
            $currentLogger->writeWarning($message, $context, $stack);
        }
    };
    $writeMessage = function ($message, $context = null) use (&$loggers)
    {
        foreach ($loggers as $currentLogger) {
            /* @var \Dcp\HttpApi\V1\Logger\Logger $currentLogger */
            $currentLogger->writeMessage($message, $context);
        }
    };
    if (is_array($customLogger)) {
        foreach ($customLogger as $currentLogger) {
            $newLogger = new $currentLogger();
            if (is_a($newLogger, '\Dcp\HttpApi\V1\Logger\Logger')) {
                $loggers[] = $newLogger;
            }
        }
    } else {
        throw new \Dcp\HttpApi\V1\Api\Exception("Unable to read custom logger, you should check the custom logger conf.");
    }
    $defaultPageMessage = function ()
    {
        $coreURL = \ApplicationParameterManager::getScopedParameterValue("CORE_URLINDEX");
        $defaultURL = $coreURL . \ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "DEFAULT_PAGE");
        $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
        $message->contentText = sprintf("You can consult %s to have info on the API", $defaultURL);
        $message->contentHtml = sprintf('You can consult <a href="%s">the REST page</a> to have info on the API', $defaultURL);
        $message->type = Dcp\HttpApi\V1\Api\RecordReturnMessage::DEBUG;
        return $message;
    };
    //endRegion initLogger
    //region Authentification
    if (file_exists('maintenance.lock')) {
        $exception = new Dcp\HttpApi\V1\Api\Exception("Maintenance in progress");
        $exception->setHttpStatus(503, "Service Unavailable");
        $exception->setUserMessage("Maintenance in progress");
        throw $exception;
    }
    
    \Dcp\ConsoleTime::step("Init");
    $authtype = getAuthType();
    
    if ($authtype == 'apache') {
        // Apache has already handled the authentication
        global $_SERVER;
        if ($_SERVER['PHP_AUTH_USER'] == "") {
            $exception = new Dcp\HttpApi\V1\Api\Exception("User must be authenticated");
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
    } else {
        // Ask authentification if HTML required
        $noAskAuthent = (preg_match("/\\.html$/", $_SERVER["REQUEST_URI"]) === 0);
        $status = AuthenticatorManager::checkAccess(null, $noAskAuthent);
        
        switch ($status) {
            case 0: // it'good, user is authentified
                break;

            default:
                $auth = AuthenticatorManager::$auth;
                if ($auth === false) {
                    $exception = new Dcp\HttpApi\V1\Api\Exception("Could not get authenticator");
                    $exception->setHttpStatus("500", "Could not get authenticator");
                    $exception->setUserMessage("Could not get authenticator");
                    throw $exception;
                }
        }
        $_SERVER['PHP_AUTH_USER'] = AuthenticatorManager::$auth->getAuthUser();
    }
    // First control
    if (empty($_SERVER['PHP_AUTH_USER'])) {
        $exception = new Dcp\HttpApi\V1\Api\Exception("User must be authenticated");
        $exception->setHttpStatus("403", "Forbidden");
        throw $exception;
    }
    
    \Dcp\ConsoleTime::step("Auth");
    //endregion Authentification
    //Initialize return object
    global $action;
    WhatInitialisation(AuthenticatorManager::$session);
    initMainVolatileParam($action->parent);
    $action->name = "HTTPAPI_V1";
    setSystemLogin($_SERVER['PHP_AUTH_USER']);
    $messages = array();
    //Routing
    \Dcp\ConsoleTime::step("Init action");
    $data = Dcp\HttpApi\V1\Api\Router::execute($messages, $httpStatus);
    
    $return->setData($data);
    $return->setHttpStatusHeader($httpStatus);
    foreach ($messages as $message) {
        $return->addMessage($message);
    }
    // Handle DCP warning message
    $warnings = $action->parent->getWarningMsg();
    foreach ($warnings as $warning) {
        $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
        $message->contentText = $warning;
        $message->type = $message::WARNING;
        $return->addMessage($message);
    }
    $action->parent->clearWarningMsg();
    // Handle DCP log message
    $warnings = $action->parent->getLogMsg();
    foreach ($warnings as $warning) {
        $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
        $message->contentText = stripslashes($warning);
        $message->type = $message::NOTICE;
        $return->addMessage($message);
    }
    $action->parent->clearLogMsg();
} //region ErrorCatching
catch(Dcp\HttpApi\V1\Etag\Exception $exception) {
    header("Cache-Control: private, no-cache, must-revalidate", true);
    return;
}
catch(Dcp\HttpApi\V1\Crud\Exception $exception) {
    $return->setHttpStatusCode($exception->getHttpStatus() , $exception->getHttpMessage());
    $return->exceptionMessage = $exception->getDcpMessage();
    $return->success = false;
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
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
    
    $writeError("API Exception " . $message->contentText, null, $exception->getTraceAsString());
    $return->addMessage($message);
}

catch(Dcp\HttpApi\V1\Api\Exception $exception) {
    
    $return->setHttpStatusCode($exception->getHttpStatus() , $exception->getHttpMessage());
    $return->exceptionMessage = $exception->getDcpMessage();
    $return->success = false;
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
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
    $writeError("API Exception " . $message->contentText, null, $exception->getTraceAsString());
    $return->addMessage($message);
    $return->addMessage($defaultPageMessage());
}
catch(\Dcp\Exception $exception) {
    $return->setHttpStatusCode(400, "Dcp Exception");
    $return->success = false;
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
    $message->contentText = $exception->getDcpMessage();
    $message->type = $message::ERROR;
    $message->code = $exception->getDcpCode();
    $return->addMessage($message);
    $writeError("DCP Exception " . $message->contentText, null, $exception->getTraceAsString());
}
catch(\Exception $exception) {
    $return->setHttpStatusCode(400, "Exception");
    $return->success = false;
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
    $message->contentText = $exception->getMessage();
    $message->type = $message::ERROR;
    $message->code = "API0001";
    $return->addMessage($message);
    $writeError("PHP Exception " . $message->contentText, null, $exception->getTraceAsString());
}
//endregion ErrorCatching
//Send the HTTP return
$headers = headers_list();
foreach ($headers as $currentHeader) {
    if (mb_strpos($currentHeader, "ETag") === 0) {
        header("Cache-Control: private, no-cache, must-revalidate", true);
        header_remove("Pragma");
        header_remove("Expires");
    }
}
\Dcp\ConsoleTime::step("complete");
if ($tracing === "TRUE") {
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
    $message->contentText = \Dcp\ConsoleTime::getDisplay();
    $message->type = $message::NOTICE;
    $return->addMessage($message);
}

$return->setReturnMode(\Dcp\HttpApi\V1\Api\Router::getExtension());
$return->send();
