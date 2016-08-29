<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Etag;

class Manager
{
    /**
     * Verify the etag validity against the If-None-Match header
     * @param $etag
     * @return bool
     */
    public static function verifyCache($etag)
    {
        $etagFromClient = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : "";
        if ($etagFromClient) {
            //Handle add of -gzip to etag by apache in mode deflate
            $etagFromClient = str_replace("-gzip", "", $etagFromClient);
            return $etagFromClient === "\"$etag\"";
        } else {
            return false;
        }
    }
    /**
     * Generate the header for the etag response
     *
     * @param $etag
     */
    public static function generateResponseHeader($etag)
    {
        header("Cache-Control: private");
        header("ETag: \"$etag\"");
    }
    /**
     * Generate the header for the static response
     */
    public static function generateNotModifiedResponse($etag)
    {
        header('Not Modified', true, 304);
        header("ETag: \"$etag\"");
        header('Connection: close');
    }
    
    public static function setEtagHeaders()
    {
        header("Cache-Control: private, no-cache, must-revalidate", true);
        header_remove("Pragma");
        header_remove("Expires");
    }
}
