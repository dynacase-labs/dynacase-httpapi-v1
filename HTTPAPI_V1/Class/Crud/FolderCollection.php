<?php
/*
 * @author Anakeen
 * @package FDL
 */
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 06/11/14
 * Time: 12:15
 */

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager;

class FolderCollection extends DocumentCollection
{
    /**
     * @var \Doc document instance
     */
    protected $_document = null;
    
    protected function prepareSearchDoc()
    {
        $ressourceId = $this->urlParameters["identifier"];
        DocumentUtils::checkDocumentId($ressourceId, "folders/%s/");
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
        if ($this->_document->doctype !== "D") {
            $exception = new Exception("CRUD0504", $ressourceId);
            $exception->setHttpStatus("400", "The document is not a directory");
            $exception->setURI($this->generateURL(sprintf("documents/%d.json", $this->_document->initid)));
            throw $exception;
        }
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn();
        $this->_searchDoc->useCollection($ressourceId);
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
        if ($this->hasFields(self::GET_ATTRIBUTE)) {
            return DocumentUtils::getAttributesFields($this->_document, $prefix, $fields);
        }
        return array();
    }
    
    public function generateURL($path, $query = null)
    {
        if ($path === "documents/") {
            $path = sprintf("folders/%s/", $this->_document->initid);
        }
        return parent::generateURL($path, $query);
    }
    
    protected function extractOrderBy()
    {
        $orderBy = isset($this->contentParameters["orderBy"]) ? $this->contentParameters["orderBy"] : "title:asc";
        return DocumentUtils::extractOrderBy($orderBy, $this->_document);
    }
    protected function getCollectionProperties()
    {
        return array(
            "title" => $this->_document->getTitle() ,
            "uri" => $this->generateURL(sprintf("documents/%d.json", $this->_document->initid))
        );
    }
}
