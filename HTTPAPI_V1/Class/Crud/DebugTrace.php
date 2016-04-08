<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class DebugTrace
{
    private static $debug = array();
    
    static public function addTrace($message)
    {
        static $previous;
        static $start;
        
        $mb = microtime(true);
        if ($previous) {
            $delay = $mb - $previous;
        } else {
            $delay = 0;
            $start = $mb;
        }
        $previous = $mb;
        self::$debug[] = sprintf("%.03fms %.03fms: %s;", $delay, ($mb - $start) , $message);
    }
    
    static public function getTraces()
    {
        return self::$debug;
    }
}
