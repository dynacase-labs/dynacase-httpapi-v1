<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class apiRouterV1
{
    protected static $resource = null;
    protected static $resource_id = null;
    protected static $resource_format = null;
    protected static $subresource = null;
    
    private static function parseAcceptHeader()
    {
        /* TODO:
         * - Parse "Accept:" HTTP header to infer prefered output format
        */
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return null;
        }
        return null;
    }
    private static function parseRequest()
    {
        $pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $pathInfo = ltrim($pathInfo, '/');
        /* Extract extension for format */
        if (preg_match('/^(?P<path>.*)\.(?P<ext>[a-z]+)$/', $pathInfo, $m)) {
            self::$resource_format = $m['ext'];
            $pathInfo = $m['path'];
        }
        if (self::$resource_format === null) {
            self::$resource_format = self::parseAcceptHeader();
        }
        /* Parse path elements */
        $elmts = preg_split(':/:', $pathInfo);
        self::$resource = array_shift($elmts);
        if (self::$resource === null) {
            throw new Exception("API0100");
        }
        
        switch (self::$resource) {
            case 'documents':
                self::$resource_id = array_shift($elmts);
                break;

            case 'enums':
                self::$subresource = array_shift($elmts);
                self::$resource_id = array_shift($elmts);
                break;
            case 'families':
                self::$resource_id = array_shift($elmts);
                self::$subresource = array_shift($elmts);
                break;
        }
        if (self::$resource_id !== null) {
            $_GET['id'] = self::$resource_id;
        }
    }
    /**
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
