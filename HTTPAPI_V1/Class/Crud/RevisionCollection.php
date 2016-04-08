<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager;

class RevisionCollection extends DocumentCollection
{
    
    protected $_document = null;
    protected $rootLevel = "documents";
    /**
     * Read a ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     */
    public function read($resourceId)
    {
        $documentList = $this->prepareDocumentList();
        $return = array(
            "requestParameters" => array(
                "slice" => $this->slice,
                "offset" => $this->offset,
                "length" => count($documentList) ,
                "orderBy" => $this->orderBy
            )
        );
        $return["uri"] = $this->generateURL(sprintf("%s/%s/revisions/", $this->rootLevel, $this->urlParameters["identifier"]));
        $documentFormatter = $this->prepareDocumentFormatter($documentList);
        $documentFormatter->addProperty("revision");
        $documentFormatter->addProperty("status");
        $data = $documentFormatter->format();
        foreach ($data as & $currentData) {
            $currentData["uri"] = $this->generateURL(sprintf("%s/%d/revisions/%d.json", $this->rootLevel, $currentData["properties"]["initid"], $currentData["properties"]["revision"]));
        }
        $return["revisions"] = $data;
        
        return $return;
    }
    
    protected function prepareSearchDoc()
    {
        $ressourceId = $this->urlParameters["identifier"];
        DocumentUtils::checkDocumentId($ressourceId, "documents/%s/revisions/");
        $this->_document = DocManager::getDocument($ressourceId);
        if (!$this->_document) {
            $exception = new Exception("CRUD0200", $ressourceId);
            $exception->setHttpStatus("404", "Document not found");
            throw $exception;
        }
        if ($this->_document->doctype === "Z") {
            $exception = new Exception("CRUD0219", $ressourceId);
            $exception->setHttpStatus("404", "Document deleted");
            $exception->setURI($this->generateURL(sprintf("trash/%d/revisions/", $this->_document->initid)));
            throw $exception;
        }
        
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn(true);
        $this->_searchDoc->addFilter("initid = %d", $this->_document->initid);
        $this->orderBy = $this->extractOrderBy();
        $this->_searchDoc->setOrder($this->orderBy);
        $this->_searchDoc->latest = false;
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
            return DocumentUtils::getAttributesFields($this->_document, $prefix, $fields);
        }
        return array();
    }
    
    protected function extractOrderBy()
    {
        $orderBy = isset($this->contentParameters["orderBy"]) ? $this->contentParameters["orderBy"] : "revision:desc";
        return DocumentUtils::extractOrderBy($orderBy, $this->_document);
    }
}
