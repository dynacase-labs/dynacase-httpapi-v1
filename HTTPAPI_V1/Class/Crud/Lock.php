<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Lock extends Crud
{
    protected $baseURL = "documents";
    /**
     * @var \Doc
     */
    protected $_document = null;
    /**
     * @var \DocFam
     */
    protected $_family = null;
    
    protected $slice = - 1;
    
    protected $offset = 0;
    
    protected $temporaryLock = false;
    protected $lockType = "permanent";
    //region CRUD part
    
    /**
     * Create new tag ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $resourceId = $this->urlParameters["identifier"];
        $this->setDocument($resourceId);
        $err = $this->_document->lock($this->temporaryLock);
        
        if ($err) {
            $exception = new Exception("CRUD0231", $err);
            throw $exception;
        }
        $this->setHttpStatus("201 Lock Created");
        return $this->getLockInfo();
    }
    /**
     * Gettag ressource
     *
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $this->setDocument($resourceId);
        
        return $this->getLockInfo();
    }
    /**
     * Update or create a tag  ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        return $this->create();
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $this->setDocument($resourceId);
        
        if ($this->temporaryLock && $this->_document->locked > 0) {
            
            $exception = new Exception("CRUD0233", $this->_document->getTitle());
            throw $exception;
        }
        if (!$this->temporaryLock && $this->hasTemporaryLock()) {
            $exception = new Exception("CRUD0234", $this->_document->getTitle());
            throw $exception;
        }
        
        $err = $this->_document->unlock($this->temporaryLock);
        
        if ($err) {
            $exception = new Exception("CRUD0232", $err);
            throw $exception;
        }
        
        return $this->getLockInfo();
    }
    //endregion CRUD part
    protected function hasTemporaryLock()
    {
        return ($this->_document->locked < - 1);
    }
    
    protected function getLockInfo()
    {
        $info = array();
        
        if ($this->_document->locked == - 1) {
            $lock = null;
        } elseif ($this->_document->locked == 0) {
            $lock = null;
        } else {
            $lock = array(
                
                "lockedBy" => array(
                    "id" => abs($this->_document->locked) ,
                    "title" => \Account::getDisplayName(abs($this->_document->locked))
                ) ,
                "isMyLock" => (abs($this->_document->locked) == getCurrentUser()->id) ,
                "temporary" => $this->hasTemporaryLock() ,
                "fixed" => false
            );
        }
        $info["uri"] = $this->generateURL(sprintf("%s/%s/locks/%s", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid, ($this->hasTemporaryLock()) ? "temporary" : "permanent"));
        
        $info["lock"] = $lock;
        return $info;
    }
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
        
        if ($this->_document->doctype === "Z") {
            $exception = new Exception("CRUD0219", $resourceId);
            $exception->setHttpStatus("404", "Document deleted");
            $exception->setURI($this->generateURL(sprintf("trash/%d.json", $this->_document->id)));
            throw $exception;
        }
        $err = $this->_document->control("view");
        if ($err) {
            $exception = new Exception("CRUD0201", $resourceId, $err);
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
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
        if ($familyId !== false) {
            $this->_family = DocManager::getFamily($familyId);
            if (!$this->_family) {
                $exception = new Exception("CRUD0200", $familyId);
                $exception->setHttpStatus("404", "Family not found");
                throw $exception;
            }
        }
        $this->lockType = isset($this->urlParameters["lockType"]) ? $this->urlParameters["lockType"] : null;
        $this->temporaryLock = ($this->lockType === "temporary");
    }
    /**
     * Generate the etag info for the current ressource
     *
     * @return null|string
     * @throws \Dcp\Db\Exception
     */
    public function getEtagInfo()
    {
        if (isset($this->urlParameters["identifier"])) {
            $id = $this->urlParameters["identifier"];
            $id = DocManager::getIdentifier($id, true);
            $sql = sprintf("select id, locked from docread where id = %d", $id);
            simpleQuery(getDbAccess() , $sql, $result, false, true);
            $result[] = getCurrentUser()->id;
            $result[] = \ApplicationParameterManager::getScopedParameterValue("WVERSION");
            
            return join("", $result);
        }
        return null;
    }
}
