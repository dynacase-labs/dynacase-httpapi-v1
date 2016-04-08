<?php
/*
 * @author Anakeen
 * @package FDL
*/
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 06/11/14
 * Time: 16:40
 */

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager;

class TrashRevisionCollection extends RevisionCollection
{
    
    protected $rootLevel = "trash";
    
    protected function prepareSearchDoc()
    {
        $ressourceId = $this->urlParameters["identifier"];
        DocumentUtils::checkDocumentId($ressourceId, "trash/%s/revisions/");
        $this->_document = DocManager::getDocument($ressourceId);
        if (!$this->_document) {
            $exception = new Exception("CRUD0200", $ressourceId);
            $exception->setHttpStatus("404", "Document not found");
            throw $exception;
        }
        if ($this->_document->doctype !== "Z") {
            $exception = new Exception("CRUD0219", $ressourceId);
            $exception->setHttpStatus("404", "Document is not deleted");
            $exception->setURI($this->generateURL(sprintf("documents/%d/revisions/", $this->_document->initid)));
            throw $exception;
        }
        
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn(true);
        $this->_searchDoc->addFilter("initid = %d", $this->_document->initid);
        $this->orderBy = $this->extractOrderBy();
        $this->_searchDoc->setOrder($this->orderBy);
        $this->_searchDoc->latest = false;
        $this->_searchDoc->trash = "also";
    }
}
