<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 06/11/14
 * Time: 17:53
 */

namespace Dcp\HttpApi\V1\Crud;


use Dcp\HttpApi\V1\DocManager\DocManager;

class TrashHistory extends History {

    protected $baseURL = "trash";

    /**
     * Find the current document and set it in the internal options
     *
     * @param $resourceId
     * @throws Exception
     */
    protected function setDocument($resourceId)
    {
        $this->_document = DocManager::getDocument($resourceId);
        if (!$this->_document) {
            $exception = new Exception("CRUD0200", $resourceId);
            $exception->setHttpStatus("404", "Document not found");
            throw $exception;
        }

        if ($this->_family && !is_a($this->_document, sprintf("\\Dcp\\Family\\%s", $this->_family->name))) {
            $exception = new Exception("CRUD0220", $resourceId, $this->_family->name);
            $exception->setHttpStatus("404", "Document is not a document of the family " . $this->_family->name);
            throw $exception;
        }

        if ($this->_document->doctype !== "Z") {
            $exception = new Exception("CRUD0219", $resourceId);
            $exception->setHttpStatus("404", "Document not deleted");
            $exception->setURI($this->generateURL(sprintf("documents/%d.json", $this->_document->id)));
            throw $exception;
        }
    }

    /**
     * @return \SearchDoc
     */
    protected function prepareSearchDoc()
    {
        $search = new \SearchDoc();
        $search->addFilter("initid = %d", $this->_document->initid);
        $search->setOrder("revision desc");
        if ($this->revisionFilter >= 0) {
            $search->addFilter("revision = %d", $this->revisionFilter);
        }
        if ($this->slice > 0) {
            $search->setSlice($this->slice);
        }
        if ($this->offset > 0) {
            $search->setStart($this->offset);
        }
        $search->setObjectReturn();
        $search->latest = false;
        $search->trash = "also";
        return $search;
    }

} 