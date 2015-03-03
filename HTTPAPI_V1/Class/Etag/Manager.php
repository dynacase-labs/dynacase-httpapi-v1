<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Etag;

class Manager {

    /**
     * Verify the etag validity against the If-None-Match header
     * @param $etag
     * @return bool
     */
    public function verifyCache($etag)
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
    function generateResponseHeader($etag)
    {
        header("Cache-Control: private");
        header("ETag: \"$etag\"");
    }

    /**
     * Generate the header for the static response
     */
    function generateNotModifiedResponse($etag)
    {
        header('Not Modified', true, 304);
        header("ETag: \"$etag\"");
        header('Connection: close');
    }


} 