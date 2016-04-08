<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager;

class SearchCollection extends DocumentCollection
{
    /**
     * @var \Doc document instance
     */
    protected $_document = null;
    
    protected function prepareSearchDoc()
    {
        $ressourceId = $this->urlParameters["identifier"];
        DocumentUtils::checkDocumentId($ressourceId, "searches/%s/");
        $this->_document = DocManager::getDocument($ressourceId);
        if (!$this->_document) {
            $exception = new Exception("CRUD0200", $ressourceId);
            $exception->setHttpStatus("404", "Document not found");
            throw $exception;
        }
        if ($this->_document->doctype === "Z") {
            $exception = new Exception("CRUD0219", $ressourceId);
            $exception->setHttpStatus("404", "Document deleted");
            $exception->setURI($this->generateURL(sprintf("trash/%d.json", $this->_document->initid)));
            throw $exception;
        }
        if ($this->_document->doctype !== "S") {
            $exception = new Exception("CRUD0503", $ressourceId);
            $exception->setHttpStatus("400", "The document is not a search");
            $exception->setURI($this->generateURL(sprintf("documents/%d.json", $this->_document->initid)));
            throw $exception;
        }
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn();
        $this->_searchDoc->useCollection($ressourceId);
    }
    
    protected function getCollectionProperties()
    {
        return array(
            "title" => $this->_document->getTitle() ,
            "uri" => $this->generateURL(sprintf("documents/%d.json", $this->_document->initid))
        );
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
        $famid = $this->_document->getAttributeValue("se_famid");
        $docFam = null;
        if ($famid) {
            $docFam = DocManager::getDocument($famid);
        }
        if ($this->hasFields(self::GET_ATTRIBUTES)) {
            return DocumentUtils::getAttributesFields($docFam, $prefix, $fields);
        }
        return array();
    }
    
    public function generateURL($path, $query = null)
    {
        if ($path === "documents/") {
            $path = sprintf("searches/%s/documents/", $this->_document->id);
        }
        return parent::generateURL($path, $query);
    }
    
    protected function extractOrderBy()
    {
        $orderBy = isset($this->contentParameters["orderBy"]) ? $this->contentParameters["orderBy"] : "title:asc";
        return DocumentUtils::extractOrderBy($orderBy, $this->_document);
    }
}
