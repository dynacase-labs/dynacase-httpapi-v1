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
        if ($systemFound !== false && $customCrud !== false && $customFound["order"] >= $systemFound["order"]) {
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
        $method = static::convertActionToCrud();
        $identifiedCrud = self::identifyCRUD();
        $crud = new $identifiedCrud["class"]();
        /* @var Crud $crud */
        $crud->setUrlParameters($identifiedCrud["param"]);
        $crud->setContentParameters(static::extractContentParameters($method));
        $return = $crud->execute($method, $messages);
        return $return;
    }

    public static function extractContentParameters($method)
    {
        if ($method === Crud::READ) {
            return $_GET;
        }
        if ($method === Crud::UPDATE || $method === Crud::CREATE) {
            return static::getHttpAttributeValues();
        }
        return array();
    }

    /**
     * Analyze the content type and return the values
     *
     * @return Array
     * @throws Exception
     */
    protected static function getHttpAttributeValues()
    {
        if (preg_match('/(x-www-form-urlencoded|form-data)/', $_SERVER["CONTENT_TYPE"])) {
            return static::getFormAttributeValues();
        } elseif (preg_match('/application\/json/', $_SERVER["CONTENT_TYPE"])) {
            return static::getJSONAttributeValues();
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
    protected static function getJSONAttributeValues()
    {
        $body = file_get_contents("php://input");
        $dataDocument = json_decode($body, true);
        if ($dataDocument === null) {
            throw new Exception("API0208", $body);
        }
        if (!isset($dataDocument["document"]["attributes"]) || !is_array($dataDocument["document"]["attributes"])) {
            throw new Exception("API0209", $body);
        }
        $values = $dataDocument["document"]["attributes"];

        $newValues = array();
        foreach ($values as $aid => $value) {
            if (!array_key_exists("value", $value) && is_array($value)) {
                $multipleValues = array();
                foreach ($value as $singleValue) {

                    if (!array_key_exists("value", $singleValue) && is_array($singleValue)) {
                        $multipleSecondLevelValues = array();
                        foreach ($singleValue as $secondVValue) {
                            $multipleSecondLevelValues[] = $secondVValue["value"];
                        }
                        $multipleValues[] = $multipleSecondLevelValues;
                    } else {
                        $multipleValues[] = $singleValue["value"];
                    }
                }
                $newValues[$aid] = $multipleValues;
            } else {
                if (!array_key_exists("value", $value)) {
                    throw new Exception("API0210", $body);
                }
                $newValues[$aid] = $value["value"];
            }
        }
        return $newValues;
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
            parse_str(file_get_contents("php://input"), $values);
        }
        $newValues = array();
        foreach ($values as $attrid => $value) {
            $newValues[strtolower($attrid)] = $value;
        }
        return $newValues;
    }

    /**
     * @return string
     */
    protected static function convertActionToCrud() {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            return Crud::READ;
        } elseif ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"])) {
            return Crud::CREATE;
        } elseif ($_SERVER["REQUEST_METHOD"] === "PUT" || $_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"] === "PUT") {
            return Crud::UPDATE;
        } elseif ($_SERVER["REQUEST_METHOD"] === "DELETE" || $_SERVER["HTTP_X_HTTP_METHOD_OVERRIDE"] === "DELETE") {
            return Crud::DELETE;
        }
        $exception = new Exception("Unable to find the CRUD method");
        throw new $exception;
    }

}
