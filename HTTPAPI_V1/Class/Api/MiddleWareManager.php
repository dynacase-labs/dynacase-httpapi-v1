<?php
namespace Dcp\HttpApi\V1\Api;

use Dcp\HttpApi\V1\Crud\MiddleWare;

class MiddleWareManager
{
    /**
     * Execute the middleware requests
     * Execute all before middleware compatible with request
     *
     * @param array        $processInfo
     * @param HttpRequest  $request current CRUD method requireds : CREATE/READ/UPDATE/DELETE
     * @param HttpResponse $response
     *
     * @return void
     */
    public static function preProcess(array $processInfo, HttpRequest $request, HttpResponse $response)
    {
        
        foreach ($processInfo["preProcessMiddleWare"] as $k => $info) {
            self::process($info, $request, $response);
        }
    }
    
    public static function postProcess(array $processInfo, HttpRequest $request, HttpResponse $response)
    {
        foreach ($processInfo["postProcessMiddleWare"] as $info) {
            self::process($info, $request, $response);
        }
    }
    
    protected static function process(array $info, HttpRequest $request, HttpResponse & $response)
    {
        if ($response->responseIsStopped()) {
            return;
        }
        $response->addHeader("X-Dcp-Middleware", $info["description"], false);
        
        $class = $info["class"];
        if (!$class) {
            throw new Exception("CRUD0107", $info["description"]);
        }
        if ($class[0] === "/") {
            $class = "/" . $class;
        }
        if (!class_exists($class)) {
            throw new Exception("CRUD0106", $class);
        }
        /**
         * @var MiddleWare $middleObject
         */
        $middleObject = new $class($request, $response);
        if (!is_a($middleObject, '\Dcp\HttpApi\V1\Crud\MiddleWare')) {
            throw new Exception("CRUD0108", $class);
        }
        
        $identifier = isset($info["param"]["identifier"]) ? $info["param"]["identifier"] : null;
        switch ($request->getMethod()) {
            case "POST":
                $middleObject->create();
                break;

            case "GET":
                $middleObject->read($identifier);
                break;

            case "PUT":
                $middleObject->update($identifier);
                break;

            case "DELETE":
                $middleObject->delete($identifier);
                break;
        }
    }
}
