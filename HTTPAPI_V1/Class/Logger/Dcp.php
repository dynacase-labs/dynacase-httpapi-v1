<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Logger;


class Dcp extends Logger
{

    public function __construct()
    {
        $this->logger = new \Log(false, "HTTAPI_V1", "LOGGER");
    }

    public function writeError($message, $context = null, $stack = null, $exception = null)
    {
        if ($context === null && \Doc::getUserId()) {
            $context = "User : " . $this->getUserInfo();
        }
        $stack = preg_replace('!\s+!', ' ', $stack);
        $logMessage = sprintf("## Message : %s ## Context : %s ## Stack : %s", $message, $context, $stack);
        $this->logger->error($logMessage);
    }

    public function writeMessage($message, $context)
    {
        ;
        if ($context === null && \Doc::getUserId()) {
            $context = "User : " . $this->getUserInfo();
        }
        $logMessage = sprintf("## Message : %s ## Context : %s", $message, $context);
        $this->logger->info($logMessage);
    }

    public function writeWarning($message, $context = null, $stack = null)
    {
        if ($context === null && \Doc::getUserId()) {
            $context = "User : " . $this->getUserInfo();
        }
        $stack = preg_replace('!\s+!', ' ', $stack);
        $logMessage = sprintf("## Message : %s ## Context : %s ## Stack : %s", $message, $context, $stack);
        $this->logger->error($logMessage);
    }

    protected function getUserInfo()
    {
        return \Doc::getUserId() . " " . \Doc::getUserName();
    }
} 