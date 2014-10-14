<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class ApiRouterV1
{
    protected static $path = null;
    protected static $returnType = null;

    protected static function extractExtension()
    {
        $extension = false;

        $pathInfo = static::$path;
        /* Extract extension for format */
        if (preg_match('/^(?P<path>.*)\.(?P<ext>[a-z]+)$/', $pathInfo, $matches)) {
            $extension = $matches['ext'];
            static::$path = $matches['path'];
        }
        if ($extension === "json" || $extension === false) {
            $format = "application/json";
        } else {
            throw new Exception("API0005", $extension);
        }
        return $format;
    }

    protected static function parseAcceptHeader()
    {
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? mb_strtolower($_SERVER['HTTP_ACCEPT']) : "application/json";
        $accept = explode(",", $accept);
        $accept = array_map(function ($header) {
            return preg_replace("/;.*/", "", $header);
        }, $accept);

        if (!in_array("application/json", $accept) && !in_array("*/*", $accept)) {
            throw new Exception("API0005", join(",", $accept));
        }
        return "application/json";
    }

    protected static function identifyCRUD()
    {
        $systemCrud = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "SYSTEM_CRUD_CLASS"), true);
        $customCrud = json_decode(\ApplicationParameterManager::getParameterValue("HTTPAPI_V1", "CUSTOM_CRUD_CLASS"), true);
        usort($systemCrud, function ($value1, $value2) {
            return $value1["order"] < $value2["order"];
        });
        usort($customCrud, function ($value1, $value2) {
            return $value1["order"] < $value2["order"];
        });

        $customFound = false;
        foreach ($customCrud as $currentCrud) {
            $param = array();
            if (preg_match($currentCrud["regExp"], static::$path, $param) === 1) {
                $currentCrud["param"] = $param;
                $customFound = $currentCrud;
            }
        }
        $systemFound = false;
        foreach ($systemCrud as $currentCrud) {
            $param = array();
            if (preg_match($currentCrud["regExp"], static::$path, $param) === 1) {
                $currentCrud["param"] = $param;
                $customFound = $currentCrud;
            }
        }
        if ($systemFound === false && $customFound === false) {
            throw new Exception("API0004");
        }
        $crudFound = $systemFound;
        if ($systemFound !== false && $customCrud !== false && $customFound["order"] < $systemFound["order"]) {
            $crudFound = $customFound;
        }
        if ($crudFound === false) {
            $crudFound = $customFound;
        }
        return $crudFound;
    }

    /**
     * Execute the request
     *
     * @param array $messages
     * @return mixed
     * @throws Exception
     */
    public static function execute(array & $messages = array())
    {
        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        static::$path = $pathInfo;
        static::$returnType = static::extractExtension();
        if (static::$returnType === false) {
            static::$returnType = static::parseAcceptHeader();
        }
        $identifiedCrud = self::identifyCRUD();
        $crud = new $identifiedCrud["class"]();
        /* @var Crud $crud */
        $crud->setParameters($identifiedCrud["param"]);
        $return = $crud->execute($messages);
        return $return;
    }
}
