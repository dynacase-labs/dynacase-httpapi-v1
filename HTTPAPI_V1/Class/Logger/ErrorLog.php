<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Logger;


class ErrorLog extends Logger
{

    public function writeError($message, $context = null, $stack = null)
    {
        if ($context === null && \Doc::getUserId()) {
            $context = "User : " . $this->getUserInfo();
        }
        $stack = preg_replace('!\s+!', ' ', $stack);
        $logMessage = sprintf("Error : ## Message : %s ## Context : %s ## Stack : %s", $message, $context, $stack);
        error_log($logMessage);
    }

    public function writeMessage($message, $context)
    {

    }

    public function writeWarning($message, $context = null, $stack = null)
    {
        if ($context === null && \Doc::getUserId()) {
            $context = "User : " . $this->getUserInfo();
        }
        $stack = preg_replace('!\s+!', ' ', $stack);
        $logMessage = sprintf("Warning : ## Message : %s ## Context : %s ## Stack : %s", $message, $context, $stack);
        error_log($logMessage);
    }

    protected function getUserInfo()
    {
        return \Doc::getUserId() . " " . \Doc::getUserName();
    }
} 