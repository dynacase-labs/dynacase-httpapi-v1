<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class apiRouter
{
    
    protected static function getResource()
    {
        if (empty($_GET["resource"])) {
            throw new Exception("API0100");
        }
        $resource = $_GET["resource"];
        /* if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            parse_str(file_get_contents("php://input") , $_POST);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            parse_str(file_get_contents("php://input") , $_POST);
        }*/
        /*print_r(array(
            "get" => $_GET,
            "post" => $_POST,
            "delete" => $_DELETE,
            "put" => $_PUT
        ));*/
        
        return $resource;
    }
    
    protected static function getSubResource()
    {
        if (!empty($_GET["subresource"])) {
            return $_GET["subresource"];
        }
        return null;
    }
    /**
     * @param array $messages
     * @return mixed
     * @throws Exception
     */
    public static function execute(array & $messages = array())
    {
        $resource = self::getResource();
        if ($resource === null) {
            throw new Exception("API0100");
        }
        switch ($resource) {
            case "documents":
                $crud = new DocumentCrud();
                
                break;

            case "families":
                $familyIdentifier = self::getSubResource();
                if ($familyIdentifier) {
                    $crud = new FamilyDocumentCrud($familyIdentifier);
                } else {
                    $crud = new FamilyCrud();
                }
                break;

            case "files":
                $crud = new FileCrud();
                break;

            case "trash":
                $e = new Exception("API0101", $resource);
                $e->setHttpStatus(501, "Not implemented");
                throw $e;
                break;

            default:
                $e = new Exception("API0101", $resource);
                $e->setHttpStatus(501, "Not implemented");
                throw $e;
        }
        $a = $crud->execute($messages);
        return $a;
    }
}
