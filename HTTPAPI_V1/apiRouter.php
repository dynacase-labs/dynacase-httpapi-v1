<?php
/*
 * @author Anakeen
 * @package FDL
*/
chdir('..'); // need to be in root directory to be authenticated
require_once ('WHAT/autoload.php');
require_once ('WHAT/Lib.Main.php');

$tracing = \Dcp\HttpApi\V1\Api\Router::getHttpApiParameter("ACTIVATE_TRACE");
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
            $errorMessage= \Dcp\Core\LogException::getMessage($error, $errId, $logMessage);
            $message->contentText = sprintf("[%s] %s", $errId, $errorMessage);
            $message->type = $message::ERROR;
            $return->addMessage($message);
            $return->exceptionMessage = $message->contentText;
            $return->success = false;
            $return->send();
            foreach ($loggers as $currentLogger) {
                /* @var \Dcp\HttpApi\V1\Logger\Logger $currentLogger */
                $currentLogger->writeError( sprintf("[%s] %s", $errId, $logMessage), "fatal");
            }
        }
    }
};

register_shutdown_function($jsonFatalShutdown);
//endregion initErrorHandling
$return = new Dcp\HttpApi\V1\Api\RecordReturn();
//region initLogger
try {
    $loggerList = json_decode(\Dcp\HttpApi\V1\Api\Router::getHttpApiParameter("SYSTEM_LOGGER") , true);
    $customLogger = json_decode(\Dcp\HttpApi\V1\Api\Router::getHttpApiParameter("CUSTOM_LOGGER") , true);
    foreach ($loggerList as $currentLogger) {
        $loggers[] = new $currentLogger();
    }
    $writeError = function ($message, $context = null, $stack = null, $exception = null) use (&$loggers)
    {
        foreach ($loggers as $currentLogger) {
            /* @var \Dcp\HttpApi\V1\Logger\Logger $currentLogger */
            $currentLogger->writeError($message, $context, $stack, $exception);
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
        $defaultURL = $coreURL . \Dcp\HttpApi\V1\Api\Router::getHttpApiParameter("DEFAULT_PAGE");
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
    
    $messages = array();
    //Routing
    $response = Dcp\HttpApi\V1\Api\Router::execute();
    $messages = $response->getMessages();
    
    $return->setData($response->getBody());
    $return->setHttpStatusHeader($response->getStatusHeader());
    $return->setResponse($response->getResponse());
    foreach ($messages as $message) {
        $return->addMessage($message);
    }
    // Handle DCP warning message
    $action = \Dcp\HttpApi\V1\ContextManager::getCoreAction();
    if ($action) {
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
    }
    $response->sendHeaders();
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
    $return->data = $exception->getData();
    $message->data = $return->data;
    $message->uri = $exception->getURI();
    $return->setHeaders($exception->getHeaders());
    
    $writeError("API Exception " . $message->contentText, null, $exception->getTraceAsString() , $exception);
    $return->addMessage($message);
}

catch(Dcp\HttpApi\V1\Api\Exception $exception) {
    
    $return->setHttpStatusCode($exception->getHttpStatus() , $exception->getHttpMessage());
    $return->exceptionMessage = $exception->getDcpMessage();
    $return->success = false;
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
    $message->contentText = $exception->getUserMessage();
    if (!$message->contentText) {
        $message->contentText = $exception->getDcpMessage();
    }
    $message->type = $message::ERROR;
    $message->code = $exception->getDcpCode();
    $return->data = $exception->getData();
    $message->data = $return->data;
    $message->uri = $exception->getURI();
    
    $return->setHeaders($exception->getHeaders());
    $writeError("API Exception " . $message->contentText, null, $exception->getTraceAsString() , $exception);
    $return->addMessage($message);
    if ($exception->getHttpStatus() !== "403") {
        $return->addMessage($defaultPageMessage());
    }
}
catch(\Dcp\Exception $exception) {
    $exceptionMsg = \Dcp\Core\LogException::getMessage($exception, $errId);
    
    $return->setHttpStatusCode(400, "Dcp Exception");
    $return->success = false;
    $return->exceptionMessage = $exceptionMsg;
    
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
    $message->contentText = sprintf("[%s] %s", $errId, $exceptionMsg);
    $message->type = $message::ERROR;
    $message->code = $exception->getDcpCode();
    $return->addMessage($message);
    $writeError("DCP Exception " . $message->contentText, null, $exception->getTraceAsString() , $exception);
}
catch(\Exception $exception) {
    $return->setHttpStatusCode(400, "Exception");
    $return->success = false;
    $return->exceptionMessage = $exception->getMessage();
    
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
    $message->contentText = \Dcp\Core\LogException::getMessage($exception, $errId);
    $message->type = $message::ERROR;
    $message->code = "API0001";
    $return->addMessage($message);
    $writeError("PHP Exception " . $message->contentText, null, $exception->getTraceAsString() , $exception);
}
//endregion ErrorCatching
//Send the HTTP return
$headers = headers_list();
foreach ($headers as $currentHeader) {
    if (mb_strpos($currentHeader, "ETag") === 0) {
        \Dcp\HttpApi\V1\Etag\Manager::setEtagHeaders();
    }
}
if ($tracing === "TRUE") {
    $message = new Dcp\HttpApi\V1\Api\RecordReturnMessage();
    $message->contentText = \Dcp\ConsoleTime::getDisplay();
    $message->type = $message::NOTICE;
    $return->addMessage($message);
}

$return->setReturnMode(\Dcp\HttpApi\V1\Api\Router::getExtension());
$return->send();
