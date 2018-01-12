<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Logger;

use Dcp\Core\LogException;

class Dcp extends Logger
{
    /**
     * @var \Log
     */
    protected $logger;
    public function __construct()
    {
        $this->logger = new \Log(false, "HTTAPI_V1", "LOGGER");
        $this->logger->loghead = "DCPAPI";
    }
    
    public function writeError($message, $context = null, $stack = null, $exception = null)
    {
        if ($context === "fatal" ) {
            $logMessage=$message;
        } else {
            $logMessage = self::getMessage($message, $context, $stack);
        }

        if ($exception) {
            LogException::writeLog($exception);
            if ($context !== null) {
                 $this->logger->error($logMessage);
            }
        } elseif ($context === "fatal" ) {
            LogException::writeLogMsg($logMessage);
        } else {
            $this->logger->error($logMessage);
        }
    }
    
    public function writeMessage($message, $context)
    {
        $logMessage = self::getMessage($message, $context);
        $this->logger->info($logMessage);
    }
    
    public function writeWarning($message, $context = null, $stack = null)
    {
        $logMessage = self::getMessage($message, $context, $stack);
        $this->logger->warning($logMessage);
    }

    protected function getMessage($message, $context=null, $stack=null) {
        $logs[]=$message;
        if ($context) {
            $logs[]="- Context : ".$context;
        }
        if ($stack) {
            $stack = preg_replace('!\s+!', ' ', $stack);
            $stack = preg_replace('!#!', "\n#", $stack);
            $logs[]="- CallStack : ".$stack;
        }
        $uInfo=self::getUserInfo();
        if ($uInfo) {
            $logs[]="- User : ".$uInfo;
        }
        return implode("\n", $logs);
    }

    protected function getUserInfo()
    {
        $u = getCurrentUser();
        if ($u) {
            return sprintf("User : <%s> \"%s %s\" [%d]", $u->login, $u->firstname, $u->lastname, $u->id);
        }
        return null;
    }
}
