<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 15/10/14
 * Time: 20:25
 */

namespace Dcp\HttpApi\V1;


class EtagManager {

    /**
     * Verify the etag validity against the If-None-Match header
     * @param $etag
     * @return bool
     */
    public function verifyCache($etag)
    {
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            return trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag;
        } else {
            return false;
        }
    }

    /**
     * Generate the header for the etag response
     *
     * @param $etag
     */
    function generateResponseHeader($etag)
    {
        header("Cache-Control: private;");
        header("Content-Disposition: inline;");
        header("ETag: $etag");
    }

    /**
     * Generate the header for the static response
     */
    function generateNotModifiedResponse()
    {
        header('Not Modified', true, 304);
        header('Connection: close');
        exit(0);
    }


} 