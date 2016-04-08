<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Revision extends Document
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    
    protected $slice = - 1;
    
    protected $offset = 0;
    
    protected $revisionIdentifier = null;
    //region CRUD part
    
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create a new revision with the API");
        throw $exception;
    }
    /**
     * Get ressource
     *
     * @param string $resourceId Resource identifier
     * @param int    $revision revision number to get : Necessary if revisionIdentifier not set by crud execute
     *
     * @return mixed
     * @throws Exception
     */
    public function read($resourceId, $revision = - 1)
    {
        if ($revision !== - 1) {
            $this->revisionIdentifier = $revision;
        }
        $info = parent::read($resourceId);
        $info["revision"] = $info["document"];
        unset($info["document"]);
        return $info;
    }
    /**
     * Update the ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot change a revision");
        throw $exception;
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot delete a revision");
        throw $exception;
    }
    //endregion CRUD part
    public function execute($method, array & $messages = array() , &$httpStatus = "")
    {
        $this->initCrudParam();
        return parent::execute($method, $messages, $httpStatus);
    }
    /**
     * Find the current document and set it in the internal options
     *
     * @param $resourceId
     * @throws Exception
     */
    protected function setDocument($resourceId)
    {
        $revisedId = DocManager::getRevisedDocumentId($resourceId, $this->revisionIdentifier);
        $this->_document = DocManager::getDocument($revisedId, false);
        
        if (!$this->_document) {
            $exception = new Exception("CRUD0221", $this->revisionIdentifier, $resourceId);
            $exception->setHttpStatus("404", "Document not found");
            throw $exception;
        }
        
        if ($this->_family && !is_a($this->_document, sprintf("\\Dcp\\Family\\%s", $this->_family->name))) {
            $exception = new Exception("CRUD0220", $resourceId, $this->_family->name);
            $exception->setHttpStatus("404", "Document is not a document of the family " . $this->_family->name);
            throw $exception;
        }
        
        if ($this->_document->doctype === "Z") {
            $exception = new Exception("CRUD0219", $resourceId);
            $exception->setHttpStatus("404", "Document deleted");
            $exception->setURI($this->generateURL(sprintf("trash/%d.json", $this->_document->id)));
            throw $exception;
        }
    }
    /**
     * Init some internal params with extracted params
     *
     * @throws Exception
     */
    protected function initCrudParam()
    {
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;
        if ($familyId !== false) {
            $this->_family = DocManager::getFamily($familyId);
            if (!$this->_family) {
                $exception = new Exception("CRUD0200", $familyId);
                $exception->setHttpStatus("404", "Family not found");
                throw $exception;
            }
        }
        if ($this->urlParameters["revision"] !== "") {
            $this->revisionIdentifier = $this->urlParameters["revision"];
        }
    }
    /**
     * Generate Etag for the current revision
     *
     * @return null|string
     */
    public function getEtagInfo()
    {
        if (isset($this->urlParameters["revision"]) && isset($this->urlParameters["identifier"]) && $this->urlParameters["revision"] !== "" && $this->urlParameters["identifier"] !== "") {
            $id = $this->urlParameters["identifier"];
            if (!is_numeric($id)) {
                $id = DocManager::getIdFromName($this->urlParameters["identifier"]);
                $this->urlParameters["identifier"] = $id;
            }
            $id = DocManager::getRevisedDocumentId($id, $this->urlParameters["revision"]);
            return $this->extractEtagDataFromId($id);
        } else {
            return parent::getEtagInfo();
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
            $exception->addHeader("Location", $this->generateURL(sprintf("documents/%d/revisions/%s.json", $initid, $this->urlParameters["revision"]) , $query));
            $exception->setURI($this->generateURL(sprintf("documents/%d/revisions/%s.json", $initid, $this->urlParameters["revision"])));
            throw $exception;
        }
        return true;
    }
}
