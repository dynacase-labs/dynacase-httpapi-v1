<?php
/*
 * @author Anakeen
 * @package FDL
*/
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 03/11/14
 * Time: 19:57
 */

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;
use \Dcp\HttpApi\V1\DocManager\Exception as DocManagerException;

class FamilyDocumentCollection extends DocumentCollection
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    /**
     * @var \Doc document instance
     */
    protected $_document = null;
    /**
     * @return mixed
     * @throws DocManagerException
     * @throws Exception
     * @throws \Exception
     */
    public function create()
    {
        try {
            $this->_document = DocManager::createDocument($this->_family->id);
        }
        catch(DocManagerException $exception) {
            if ($exception->getDcpCode() === "APIDM0003") {
                $exception = new Exception("API0204", $this->_family->name);
                $exception->setHttpStatus(403, "Forbidden");
                throw $exception;
            } else {
                throw $exception;
            }
        }
        
        $newValues = $this->contentParameters;
        foreach ($newValues as $attrid => $value) {
            try {
                if ($value === null or $value === '') {
                    $this->_document->setAttributeValue($attrid, null);
                } else {
                    $this->_document->setAttributeValue($attrid, $value);
                }
            }
            catch(\Dcp\AttributeValue\Exception $e) {
                $exception = new Exception("CRUD0205", $this->_family->name, $attrid, $e->getDcpMessage());
                $exception->setHttpStatus("500", "Unable to create the document");
                $exception->setUserMEssage(___("Update failed", "HTTPAPI_V1"));
                $info = array(
                    "id" => $attrid,
                    "index" => $e->index,
                    "err" => $e->originalError ? $e->originalError : $e->getDcpMessage()
                );
                
                $exception->setData($info);
                throw $exception;
            }
        }
        
        $err = $this->_document->store($info);
        if ($err) {
            $exception = new Exception("CRUD0206", $this->_family->name, $err);
            $exception->setData($info);
            throw $exception;
        }
        $this->_document->addHistoryEntry(___("Create by HTTP API", "HTTPAPI_V1") , \DocHisto::NOTICE);
        DocManager::cache()->addDocument($this->_document);
        
        $familyDocument = new FamilyDocument();
        
        return $familyDocument->getInternal($this->_document);
    }
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
        DocumentUtils::checkFamilyId($this->urlParameters["familyId"], "families/%s/documents/");
        $this->_family = DocManager::getFamily($familyId);
        if (!$this->_family) {
            $exception = new Exception("CRUD0200", $familyId);
            $exception->setHttpStatus("404", "Family not found");
            throw $exception;
        }
    }
    
    protected function prepareSearchDoc()
    {
        $this->_searchDoc = new \SearchDoc("", $this->_family->name);
        $this->_searchDoc->setObjectReturn();
    }
    /**
     * Analyze JSON string and extract update values
     *
     * @param $jsonString
     * @return array
     * @throws Exception
     */
    public function analyseJSON($jsonString)
    {
        return DocumentUtils::analyzeDocumentJSON($jsonString);
    }
    /**
     * Get the restricted attributes
     *
     * @throws Exception
     * @return array
     */
    protected function getAttributeFields()
    {
        $prefix = self::GET_ATTRIBUTE;
        $fields = $this->getFields();
        if ($this->hasFields(self::GET_ATTRIBUTE) || $this->hasFields(self::GET_ATTRIBUTES)) {
            return DocumentUtils::getAttributesFields($this->_family, $prefix, $fields);
        }
        return array();
    }
    
    public function generateURL($path, $query = null)
    {
        if ($path === "documents/") {
            $path = sprintf("families/%s/documents/", $this->_family->name);
        }
        return parent::generateURL($path, $query);
    }
    
    protected function extractOrderBy()
    {
        $orderBy = isset($this->contentParameters["orderBy"]) ? $this->contentParameters["orderBy"] : "title:asc";
        return DocumentUtils::extractOrderBy($orderBy, $this->_family);
    }
    protected function getCollectionProperties()
    {
        return array(
            "title" => sprintf(___("%s Documents", "ddui") , $this->_family->getTitle())
        );
    }
}
