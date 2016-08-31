<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Api;

class AnalyzeURL
{
    
    static public function getBaseURL()
    {
        $coreURL = \ApplicationParameterManager::getScopedParameterValue("CORE_URLINDEX");
        $components = parse_url($coreURL);
        
        if ($coreURL) {
            if (isset($components["query"])) {
                unset($components["query"]);
            }
            if (isset($components["fragment"])) {
                unset($components["fragment"]);
            }
            $coreURL = static::unparseURL($components);
        } else {
            $coreURL = self::getUrlPath();
        }
        $baseURL = \Dcp\HttpApi\V1\Api\Router::getHttpApiParameter("REST_BASE_URL");
        return $coreURL . $baseURL;
    }
    
    protected static function getUrlPath()
    {
        $turl = @parse_url($_SERVER["REQUEST_URI"]);
        if ($turl['path']) {
            $scriptDirName = pathinfo($_SERVER["SCRIPT_FILENAME"], PATHINFO_DIRNAME);
            if (strpos($scriptDirName, DEFAULT_PUBDIR) === 0) {
                $relativeBaseFilePath = substr($scriptDirName, strlen(DEFAULT_PUBDIR));
                $script = $_SERVER["SCRIPT_NAME"];
                if ($relativeBaseFilePath) {
                    $pos = strpos($script, $relativeBaseFilePath);
                    $localPath = substr($script, 0, $pos) . '/';
                } else {
                    $localPath = dirname($script) . '/';
                }
            } else {
                if (substr($turl['path'], -1) != '/') {
                    $localPath = dirname($turl['path']) . '/';
                } else {
                    $localPath = $turl['path'];
                }
            }
            $localPath = preg_replace(':/+:', '/', $localPath);
            
            return $localPath;
        }
        return '/';
    }
    
    static protected function unparseURL($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}
