<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class UserTagCollection extends Crud
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
    
    protected $revisionFilter = - 1;
    //region CRUD part
    
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create user tag collection");
        throw $exception;
    }
    /**
     * Get ressource
     *
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $this->setDocument($resourceId);
        $err = $this->_document->control("view");
        if ($err) {
            $exception = new Exception("CRUD0201", $resourceId, $err);
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
        
        $info = array();
        
        $q = new \QueryDb($this->_document->dbaccess, "docUTag");
        $q->addQuery(sprintf("uid=%d", getCurrentUser()->id));
        $q->addQuery(sprintf("initid = %d", $this->_document->initid));
        $q->order_by = "date desc";
        $userTags = $q->Query($this->offset, $this->slice, "TABLE");
        if ($q->nb == 0) $userTags = array();
        
        $tags = array();
        /**
         * @var \DocUTag $uTag
         */
        foreach ($userTags as $uTag) {
            if ($uTag["tag"]) {
                $value = true;
                if ($uTag["comment"]) {
                    if ($json = json_decode($uTag["comment"])) {
                        $value = $json;
                    } else {
                        $value = $uTag["comment"];
                    }
                }
                
                $tags[] = array(
                    "id" => $uTag["tag"],
                    "date" => $uTag["date"],
                    "uri" => $this->generateURL(sprintf("%s/%s/usertags/%s", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid, $uTag["tag"])) ,
                    
                    "value" => $value
                );
            }
        }
        
        $info["uri"] = $this->generateURL(sprintf("%s/%s/usertags/", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid));
        $info["requestParameters"] = array(
            "slice" => $this->slice,
            "offset" => $this->offset
        );
        
        $info["userTags"] = $tags;
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
        $exception->setHttpStatus("405", "You cannot change user tag collection");
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
        $exception->setHttpStatus("405", "You cannot delete user tag collection");
        throw $exception;
    }
    //endregion CRUD part
    
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
    }
    /**
     * Set limit of revision to send
     * @param int $slice
     */
    public function setSlice($slice)
    {
        $this->slice = intval($slice);
    }
    /**
     * Set offset of revision to send
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = intval($offset);
    }
    /**
     * Analyze the parameters of the request
     *
     * @param array $parameters
     */
    public function setContentParameters(array $parameters)
    {
        parent::setContentParameters($parameters);
        
        if (isset($this->contentParameters["slice"])) {
            $this->setSlice($this->contentParameters["slice"]);
        }
        if (isset($this->contentParameters["offset"])) {
            $this->setOffset($this->contentParameters["offset"]);
        }
    }
    
    public function analyseJSON($jsonString)
    {
        return array(
            "tagValue" => $jsonString
        );
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
            $sql = sprintf("select id, date, comment from docutag where id = %d order by date desc limit 1", $id);
            simpleQuery(getDbAccess() , $sql, $result, false, true);
            $result[] = \ApplicationParameterManager::getScopedParameterValue("WVERSION");
            return join("", $result);
        }
        return null;
    }
}
