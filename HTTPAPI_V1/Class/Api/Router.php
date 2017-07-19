<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Api;

use Dcp\HttpApi\V1\Crud\Crud as Crud;
use Dcp\HttpApi\V1\Etag\Manager as EtagManager;
use Dcp\HttpApi\V1\Etag\Exception as EtagException;

class Router
{
    protected static $path = null;
    /**
     * @var string default extension
     */
    protected static $extension = null;
    
    protected static $returnType = null;
    
    protected static $availableExtension = array(
        "json" => "application/json",
        "html" => "text/html"
    );
    protected static $httpApiParameters = [];
    /**
     * Execute the request
     *
     * @throws EtagException
     * @throws Exception
     * @throws \Dcp\HttpApi\V1\Crud\Exception
     * @return HttpResponse
     */
    public static function execute()
    {
        $httpStatus = "200 OK";
        $etagManager = false;
        $etag = null;
        
        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        if (empty($pathInfo)) {
            if (!empty($_SERVER['REDIRECT_URL'])) {
                if (preg_match("@.*/api/v1/(.*)@", $_SERVER["REDIRECT_URL"], $reg)) {
                    $pathInfo = '/' . $reg[1];
                }
            }
        }
        static::$path = $pathInfo;
        $method = static::convertActionToCrud();
        $identifiedCrud = self::identifyCRUD();
        $crud = new $identifiedCrud["class"]();
        /* @var Crud $crud */
        if (!empty($identifiedCrud["acceptExtensions"])) {
            foreach ($identifiedCrud["acceptExtensions"] as $extension) {
                static::$availableExtension[$extension] = "*/*";
            }
        }
        
        static::$returnType = static::verifyExtension();
        if (static::$returnType === false) {
            static::$returnType = static::parseAcceptHeader();
        }
        
        if (isset($identifiedCrud["standalone"]) && $identifiedCrud["standalone"] === true) {
            $crud->setControlAcl(false);
        } else {
            \Dcp\HttpApi\V1\ContextManager::controlAuthent();
            \Dcp\HttpApi\V1\ContextManager::initCoreApplication();
        }
        
        if (!$crud->checkCrudPermission($method)) {
            throw new Exception("CRUD0105", $method);
        }
        $request = new HttpRequest($method, static::extractContentParameters($method, $crud));
        $response = new HttpResponse();
        
        $crud->setUrlParameters($identifiedCrud["param"]);
        $crud->setContentParameters($request->getQuery());
        $cacheControl = isset($_SERVER['HTTP_CACHE_CONTROL']) ? $_SERVER['HTTP_CACHE_CONTROL'] : false;
        if ($cacheControl !== "no-cache" && $method === Crud::READ && self::getHttpApiParameter("ACTIVATE_CACHE") === "TRUE") {
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
        if ($etagManager !== false && $etag !== false) {
            $etagManager->generateResponseHeader($etag);
        }
        $mainMessages = [];
        MiddleWareManager::preProcess($identifiedCrud, $request, $response);
        if (!$response->responseIsStopped()) {
            $return = $crud->execute($request->getCrudMethod() , $mainMessages, $httpStatus);
            $response->setStatusHeader($httpStatus);
            
            foreach ($mainMessages as $message) {
                $response->addMessage($message);
            }
            
            $response->setBody($return);
            MiddleWareManager::postProcess($identifiedCrud, $request, $response);
        }
        
        return $response;
    }
    /**
     * @return null
     */
    public static function getExtension()
    {
        if (self::$extension === null) {
            self::verifyExtension();
        }
        return self::$extension;
    }
    /**
     * Extract the extension of the current request path
     *
     * Remove the extension of the path
     *
     * @return string
     * @throws Exception
     */
    protected static function verifyExtension()
    {
        
        if (static::$extension === null || static::$extension === "") {
            $format = "application/json";
        } else {
            $availableExtension = array_keys(self::$availableExtension);
            if (!in_array(static::$extension, $availableExtension)) {
                // if acceptExtension as "*" so router accept any
                if (!array_search("*", $availableExtension)) {
                    throw new Exception("API0005", static::$extension);
                } else {
                    return "*/*";
                }
            } else {
                $format = self::$availableExtension[static::$extension];
            }
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
        // @TODO : NEVER USED MUST BE REWRITE FOR ACCEPT HTML ALSO : MAY BE UNNECESSARY
        if (!in_array("application/json", $accept) && !in_array("*/*", $accept)) {
            throw new Exception("API0006", join(",", $accept));
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
        $pathInfo = static::$path;
        /* Extract extension for format */
        if (preg_match('/^(?P<path>.*)\.(?P<ext>[a-z]+)$/', $pathInfo, $matches)) {
            static::$extension = $matches['ext'];
            static::$path = $matches['path'];
        }
        
        $systemCrud = json_decode(self::getHttpApiParameter("CRUD_CLASS") , true);
        // rules are already ordered
        $crudFound = [];
        if (static::$extension && static::$extension !== "json") {
            $searchPath = static::$path . "." . static::$extension;
        } else {
            $searchPath = static::$path;
        }
        foreach ($systemCrud as $currentCrud) {
            $param = array();
            if (preg_match($currentCrud["regExp"], $searchPath, $param) === 1) {
                $currentCrud["param"] = $param;
                $crudFound = $currentCrud;
                break;
            }
        }
        if ($crudFound === []) {
            $exception = new Exception("API0004", static::$path);
            $exception->setHttpStatus(404, "Route not found");
            throw $exception;
        }
        
        $middleware = self::identifyCRUDMiddleware();
        $crudFound = array_merge($crudFound, $middleware);
        return $crudFound;
    }
    /**
     * Identify the CRUD class
     *
     * @return array crud identified and url request
     * @throws Exception
     */
    protected static function identifyCRUDMiddleware()
    {
        $pathInfo = static::$path;
        /* Extract extension for format */
        if (preg_match('/^(?P<path>.*)\.(?P<ext>[a-z]+)$/', $pathInfo, $matches)) {
            static::$extension = $matches['ext'];
            static::$path = $matches['path'];
        }
        
        $systemCrud = json_decode(self::getHttpApiParameter("CRUD_MIDDLECLASS") , true);
        // rules are already ordered
        $crudMiddles = ["postProcessMiddleWare" => [], "preProcessMiddleWare" => []];
        if (static::$extension && static::$extension !== "json") {
            $searchPath = static::$path . "." . static::$extension;
        } else {
            $searchPath = static::$path;
        }
        foreach ($systemCrud as $currentCrud) {
            $param = array();
            if (preg_match($currentCrud["regExp"], $searchPath, $param) === 1) {
                $currentCrud["param"] = $param;
                if ($currentCrud["process"] !== "after" && $currentCrud["process"] !== "before") {
                    throw new Exception("API0107", print_r($currentCrud, true));
                }
                $index = ($currentCrud["process"] === "after") ? "postProcessMiddleWare" : "preProcessMiddleWare";
                $crudMiddles[$index][] = $currentCrud;
            }
        }
        
        return $crudMiddles;
    }
    /**
     * Extract the content of the request
     *
     * @param      $method
     * @param Crud $crudElement
     *
     * @return array
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
     * @param Crud $crudElement
     *
     * @return array
     * @throws Exception
     */
    protected static function getHttpAttributeValues(Crud $crudElement)
    {
        if (empty($_SERVER["CONTENT_TYPE"])) {
            throw new Exception("API0009");
        }
        if (preg_match('/(x-www-form-urlencoded|form-data)/', $_SERVER["CONTENT_TYPE"])) {
            return static::getFormAttributeValues();
        } elseif (preg_match('/application\/json/', $_SERVER["CONTENT_TYPE"])) {
            return static::getJSONAttributeValues($crudElement);
        } else {
            // Extraction of request content must by performed by the crud code method
            return [];
        }
    }
    /**
     * Analyze the json content of the current request and extract values
     *
     * @param Crud $crudElement
     *
     * @return array
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
     * @throws Exception
     */
    public static function convertActionToCrud()
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
        if (!isset($_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"])) {
            throw new Exception("API0007", $_SERVER["REQUEST_METHOD"]);
        } else {
            throw new Exception("API0008", $_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"], $_SERVER["REQUEST_METHOD"]);
        }
    }
    /**
     * Retrieve all application parameters in once time
     * @param $name
     *
     * @return mixed|string
     */
    public static function getHttpApiParameter($name)
    {
        if (!self::$httpApiParameters) {
            simpleQuery("", "select paramv.name, paramv.val from paramv, application where paramv.appid=application.id and application.name = 'HTTPAPI_V1'", $params);
            foreach ($params as $appParam) {
                self::$httpApiParameters[$appParam["name"]] = $appParam["val"];
            }
        }
        if (isset(self::$httpApiParameters[$name])) {
            return self::$httpApiParameters[$name];
        }
        return \ApplicationParameterManager::getParameterValue("HTTPAPI_V1", $name);
    }
}
