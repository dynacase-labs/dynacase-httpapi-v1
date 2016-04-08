<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

class LockCollection extends Lock
{
    //region CRUD part
    
    /**
     * Create new tag ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("501", "Not yet implemented");
        throw $exception;
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
        
        return $this->getInfo();
    }
    /**
     * Update or create a tag  ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("501", "Not yet implemented");
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
        $this->setDocument($resourceId);
        
        $err = $this->_document->unlock($this->temporaryLock);
        
        if ($err) {
            $exception = new Exception("CRUD0232", $err);
            throw $exception;
        }
        
        return $this->getInfo();
    }
    //endregion CRUD part
    protected function getInfo()
    {
        $locks = array();
        if ($this->_document->locked > 0 || $this->hasTemporaryLock()) {
            $locks[] = $this->getLockInfo();
        }
        
        return array(
            "uri" => $this->generateURL(sprintf("%s/%s/locks/", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid)) ,
            "locks" => $locks
        );
    }
}
