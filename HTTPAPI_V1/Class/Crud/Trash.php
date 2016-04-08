<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Trash extends Document {

    //region CRUD part
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create() {
        $exception = new Exception("CRUD0103", "create");
        $exception->setHttpStatus("405", "You cannot create a document in the trash");
        throw $exception;
    }

    /**
     * Update the ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId) {
        if (isset($this->contentParameters["document"]["properties"]["status"]) &&
            $this->contentParameters["document"]["properties"]["status"] === "alive") {
            $this->setDocument($resourceId);
            $err = $this->_document->undelete();
            $err .= $this->_document->store();
            if ($err) {
                $exception = new Exception("CRUD0505", $err);
                $exception->setHttpStatus("500", "Unable to restore the document");
                throw $exception;
            }

            $documentCRUD = new Document($this->_document);
            return $documentCRUD->read($resourceId);
        }
        $exception = new Exception("CRUD0103", "update");
        $exception->setHttpStatus("405", "You cannot update a document in the trash");
        throw $exception;
    }

    /**
     * Delete ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId) {
        $exception = new Exception("CRUD0103", "delete");
        $exception->setHttpStatus("500", "Not yet implemented");
        throw $exception;
    }
    //endregion CRUD part
    /**
     * Set the current document
     *
     * @param $resourceId
     * @throws Exception
     */
    protected function setDocument($resourceId)
    {
        $this->_document = DocManager::getDocument($resourceId);
        if (!$this->_document) {
            $e = new Exception("CRUD0200", $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }
        if ($this->_document->doctype !== "Z") {
            $e = new Exception("CRUD0236", $resourceId);
            $e->setHttpStatus("404", "Document not in the trash");
            throw $e;
        }
    }

    public function checkId($identifier)
    {
        $initid = $identifier;
        if (is_numeric($identifier)) {
            $initid = DocManager::getInitIdFromIdOrName($identifier);
        }
        if ($initid !== 0 && $initid != $identifier) {
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is a revision");
            $exception->addHeader("Location", $this->generateURL(sprintf("trash/%d.json", $initid), $query));
            $exception->setURI($this->generateURL(sprintf("trash/%d.json", $initid)));
            throw $exception;
        }
        return true;
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
        $dataDocument = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("CRUD0208", "Unable to json decode " . $jsonString);
        }
        if ($dataDocument === null) {
            throw new Exception("CRUD0208", $jsonString);
        }
        if (!isset($dataDocument["document"]["properties"]["status"]) && $dataDocument["document"]["properties"]["status"] !== "alive") {
            throw new Exception("CRUD0236", $jsonString);
        }
        return $dataDocument;
    }
} 