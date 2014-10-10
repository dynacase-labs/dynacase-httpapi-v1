<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class ApiRouterV1
{
    protected static $resource = null;
    protected static $resource_id = null;
    protected static $resource_format = null;
    protected static $subresource = null;
    
    private static function parseAcceptHeader()
    {
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? mb_strtolower($_SERVER['HTTP_ACCEPT']) : "application/json";
        $accept = explode(",", $accept);
        $accept = array_map(function($header) {
            return preg_replace("/;.*/", "", $header);
        }, $accept);

        if (!in_array("application/json", $accept) && !in_array("*/*", $accept)) {
            throw new Exception("API0005", join(",", $accept));
        }
        return "application/json";
    }

    /**
     * Analyze the path to identify the elements of the request
     *
     * @throws Exception
     */
    private static function parseRequest()
    {
        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $pathInfo = ltrim($pathInfo, '/');
        /* Extract extension for format */
        if (preg_match('/^(?P<path>.*)\.(?P<ext>[a-z]+)$/', $pathInfo, $matches)) {
            self::$resource_format = $matches['ext'];
            $pathInfo = $matches['path'];
        }
        if (self::$resource_format === null) {
            self::$resource_format = self::parseAcceptHeader();
        }
        /* Parse path elements */
        $elements = preg_split(':/:', $pathInfo);
        self::$resource = array_shift($elements);
        if (self::$resource === null) {
            throw new Exception("API0100");
        }
        
        switch (self::$resource) {
            case 'documents':
                self::$resource_id = array_shift($elements);
                break;
            case 'enums':
                self::$subresource = array_shift($elements);
                self::$resource_id = array_shift($elements);
                break;
            case 'families':
                self::$resource_id = array_shift($elements);
                self::$subresource = array_shift($elements);
                break;
        }
        if (self::$resource_id !== null) {
            $_GET['id'] = self::$resource_id;
        }
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
        self::parseRequest();
        if (self::$resource === null) {
            throw new Exception("API0100");
        }
        switch (self::$resource) {
            case "documents":
                $crud = new DocumentCrud();
                break;

            case "families":
                $familyIdentifier = self::$subresource;
                if ($familyIdentifier) {
                    $crud = new FamilyDocumentCrud($familyIdentifier);
                } else {
                    $crud = new FamilyCrud();
                }
                break;

            case "files":
                $crud = new FileCrud();
                break;

            case "enums":
                $enumIdentifier = self::$subresource;
                $crud = new EnumCrud($enumIdentifier);
                break;

            case "trash":
                $e = new Exception("API0101", self::$resource);
                $e->setHttpStatus(501, "Not implemented");
                throw $e;
                break;

            default:
                $e = new Exception("API0101", self::$resource);
                $e->setHttpStatus(501, "Not implemented");
                throw $e;
        }
        $a = $crud->execute($messages);
        return $a;
    }
}
