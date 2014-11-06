<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 06/11/14
 * Time: 09:47
 */

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\Api\AnalyzeURL;


class URLUtils {

    static public function generateURL($path, $query = null)
    {
        $url = AnalyzeURL::getBaseURL() . $path;
        if ($query) {
            $url .= "?" . $query;
        }
        return $url;
    }
} 