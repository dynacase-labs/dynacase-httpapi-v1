<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Api;

use \ApplicationParameterManager as AppParam;
use Dcp\HttpApi\V1\Crud\Crud as Crud;
use Dcp\HttpApi\V1\Etag\Manager as EtagManager;
use Dcp\HttpApi\V1\Etag\Exception as EtagException;

class Router
{
    protected static $path = null;
    protected static $returnType = null;
    /**
     * Execute the request
     *
     * @param array $messages
     * @param string $httpStatus http Status code "200 OK" by example
     * @throws EtagException
     * @throws Exception
     * @throws \Dcp\HttpApi\V1\Crud\Exception
     * @return mixed
     */
    public static function execute(array & $messages = array() , &$httpStatus = "")
    {
        $httpStatus = "200 OK";
        $etagManager = false;
        $etag = null;

        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        if (empty($pathInfo)) {
            if (!empty($_SERVER['REDIRECT_URL'])) {
                if (preg_match("@.*/api/v1/(.*)@", $_SERVER["REDIRECT_URL"], $reg)) {
                    $pathInfo='/'.$reg[1];
                }
            }
        }
        static::$path = $pathInfo;
        static::$returnType = static::extractExtension();
        if (static::$returnType === false) {
            static::$returnType = static::parseAcceptHeader();
        }
        $method = static::convertActionToCrud();
        $identifiedCrud = self::identifyCRUD();
        $crud = new $identifiedCrud["class"]();
        /* @var Crud $crud */
        $crud->setUrlParameters($identifiedCrud["param"]);
        $crud->setContentParameters(static::extractContentParameters($method, $crud));
        $cacheControl = isset($_SERVER['HTTP_CACHE_CONTROL']) ? $_SERVER['HTTP_CACHE_CONTROL'] : false;
        if ($cacheControl !== "no-cache" && $method === Crud::READ && AppParam::getParameterValue("HTTPAPI_V1", "ACTIVATE_CACHE") === "TRUE") {
            $etag = $crud->getEtagInfo();
            if ($etag !== null) {
                $etag = sha1($etag);
                $etagManager = new EtagManager();
                if ($etagManager->verifyCache($etag)) {
                    $etagManager->generateNotModifiedResponse($etag);
                    throw new EtagException();
                }
            }
        }
        $return = $crud->execute($method, $messages, $httpStatus);
        if ($etagManager !== false && $etag !== false) {
            $etagManager->generateResponseHeader($etag);
        }
        return $return;
    }
    /**
     * Extract the extension of the current request path
     *
     * Remove the extension of the path
     *
     * @return string
     * @throws Exception
     */
    protected static function extractExtension()
    {
        $extension = false;
        
        $pathInfo = static::$path;
        /* Extract extension for format */
        if (preg_match('/^(?P<path>.*)\.(?P<ext>[a-z]+)$/', $pathInfo, $matches)) {
            $extension = $matches['ext'];
            static::$path = $matches['path'];
        }
        if ($extension === "json" || $extension === false || $extension === "") {
            $format = "application/json";
        } else {
            throw new Exception("API0005", $extension);
        }
        return $format;
    }
    /**
     * Check if the accept header is present and extract it
     *
     * @return string
     * @throws Exception
     */
    protected static function parseAcceptHeader()
    {
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? mb_strtolower($_SERVER['HTTP_ACCEPT']) : "application/json";
        $accept = explode(",", $accept);
        $accept = array_map(function ($header)
        {
            return preg_replace("/;.*/", "", $header);
        }
        , $accept);
        
        if (!in_array("application/json", $accept) && !in_array("*/*", $accept)) {
            throw new Exception("API0005", join(",", $accept));
        }
        return "application/json";
    }
    /**
     * Identify the CRUD class
     *
     * @return array crud identified and url request
     * @throws Exception
     */
    protected static function identifyCRUD()
    {
        $systemCrud = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "CRUD_CLASS") , true);
        usort($systemCrud, function ($value1, $value2)
        {
            return $value1["order"] > $value2["order"];
        });
        
        $crudFound = false;
        foreach ($systemCrud as $currentCrud) {
            $param = array();
            if (preg_match($currentCrud["regExp"], static::$path, $param) === 1) {
                $currentCrud["param"] = $param;
                $crudFound = $currentCrud;
            }
        }
        if ($crudFound === false) {
            throw new Exception("API0004", static::$path);
        }
        return $crudFound;
    }
    /**
     * Extract the content of the request
     *
     * @param $method
     * @return array
     * @throws Exception
     */
    public static function extractContentParameters($method, Crud $crudElement)
    {
        if ($method === Crud::READ) {
            return $_GET;
        }
        if ($method === Crud::UPDATE || $method === Crud::CREATE) {
            return static::getHttpAttributeValues($crudElement);
        }
        return array();
    }
    /**
     * Analyze the content type and return the values
     *
     * @return array
     * @throws Exception
     */
    protected static function getHttpAttributeValues(Crud $crudElement)
    {
        if (preg_match('/(x-www-form-urlencoded|form-data)/', $_SERVER["CONTENT_TYPE"])) {
            return static::getFormAttributeValues();
        } elseif (preg_match('/application\/json/', $_SERVER["CONTENT_TYPE"])) {
            return static::getJSONAttributeValues($crudElement);
        } else {
            throw new Exception("API0003", $_SERVER["CONTENT_TYPE"]);
        }
    }
    /**
     * Analyze the json content of the current request and extract values
     *
     * @return array
     * @throws Exception
     */
    protected static function getJSONAttributeValues(Crud $crudElement)
    {
        $body = file_get_contents("php://input");
        return $crudElement->analyseJSON($body);
    }
    /**
     * Analyze the values from the form data
     *
     * @return array
     */
    protected static function getFormAttributeValues()
    {
        $values = $_POST;
        if (static::convertActionToCrud() === Crud::UPDATE) {
            parse_str(file_get_contents("php://input") , $values);
        }
        $newValues = array();
        foreach ($values as $attrid => $value) {
            $newValues[strtolower($attrid) ] = $value;
        }
        return $newValues;
    }
    /**
     * @return string
     */
    protected static function convertActionToCrud()
    {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            return Crud::READ;
        } elseif ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"])) {
            return Crud::CREATE;
        } elseif ($_SERVER["REQUEST_METHOD"] === "PUT" || (isset($_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"]) && $_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"] === "PUT")) {
            return Crud::UPDATE;
        } elseif ($_SERVER["REQUEST_METHOD"] === "DELETE" || (isset($_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"]) && $_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"] === "DELETE")) {
            return Crud::DELETE;
        }
        $exception = new Exception("Unable to find the CRUD method");
        throw new $exception;
    }
}
