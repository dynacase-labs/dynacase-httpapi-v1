<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class FamilyDocument extends Document
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    //region CRUD part
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create a document with an ID");
        throw $exception;
    }
    //endregion CRUD part
    
    /**
     * Set the family of the current request
     *
     * @param array $array
     * @throws Exception
     */
    public function setUrlParameters(Array $array)
    {
        parent::setUrlParameters($array);
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;
        $this->_family = DocManager::getFamily($familyId);
        if (!$this->_family) {
            $exception = new Exception("CRUD0200", $familyId);
            $exception->setHttpStatus("404", "Family not found");
            throw $exception;
        }
    }
    /**
     * Return the canonical URL or display a message
     *
     * @param $identifier
     * @return bool
     * @throws Exception
     */
    public function checkId($identifier)
    {
        $initid = $identifier;
        if (is_numeric($identifier)) {
            $initid = DocManager::getInitIdFromIdOrName($identifier);
        }
        if ($initid !== 0) {
            $document = DocManager::getDocument($initid);
            if (!$document) {
                $exception = new Exception("CRUD0200", $initid);
                $exception->setHttpStatus("404", "Document not found");
                throw $exception;
            }
            if (!is_a($document, sprintf("\\Dcp\\Family\\%s", $this->_family->name))) {
                $exception = new Exception("CRUD0220", $initid, $this->_family->name);
                $exception->setHttpStatus("404", "Document is not a document of the family " . $this->_family->name);
                throw $exception;
            }
            if ($document->doctype === "Z") {
                $exception = new Exception("CRUD0219", $initid);
                $exception->setHttpStatus("404", "Document deleted");
                throw $exception;
            }
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is not the canonical URI");
            $exception->addHeader("Location", $this->generateURL(sprintf("documents/%d.json", $initid), $query));
            $exception->setURI($this->generateURL(sprintf("documents/%d.json", $initid)));
            throw $exception;
        }
        return true;
    }
}
