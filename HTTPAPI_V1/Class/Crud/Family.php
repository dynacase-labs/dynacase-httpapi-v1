<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Family extends Document
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    //region CRUD part
    
    /**
     * Update a family
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot update a family");
        throw $exception;
    }
    /**
     * Delete family
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot delete a family with the API");
        throw $exception;
    }
    //endregion CRUD part
    
    /**
     * Set the current family
     *
     * @throws Exception
     */
    protected function setFamily()
    {
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;
        
        $this->_family = DocManager::getFamily($familyId);
        if ($this->_family === null) {
            $exception = new Exception("CRUD0203", $familyId);
            $exception->setHttpStatus(404, "Family not found");
            throw $exception;
        }
    }
    /**
     * Set the current document
     *
     * @param $resourceId
     * @throws Exception
     */
    protected function setDocument($resourceId)
    {
        $this->_document = DocManager::getFamily($resourceId);
        if (!$this->_document) {
            $e = new Exception("CRUD0203", $resourceId);
            $e->setHttpStatus("404", "Family not found");
            throw $e;
        }
        
        if ($this->_document->doctype === "Z") {
            // Never be came
            $e = new Exception("CRUD0219", $resourceId);
            $e->setHttpStatus("404", "Family deleted");
            throw $e;
        }
    }
    
    public function checkId($identifier)
    {
        return DocumentUtils::checkFamilyId($identifier);
    }
}
