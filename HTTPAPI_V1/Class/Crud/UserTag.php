<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class UserTag extends Crud
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
    
    protected $tagIdentifier = "";
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
        $userTag = $this->_document->getUTag($this->tagIdentifier, false);
        if ($userTag) {
            $exception = new Exception("CRUD0225", $this->tagIdentifier);
            throw $exception;
        }
        
        $err = $this->_document->addUTag(getCurrentUser()->id, $this->tagIdentifier, $this->contentParameters["tagValue"]);
        if ($err) {
            $exception = new Exception("CRUD0224", $this->tagIdentifier, $err);
            throw $exception;
        }
        return $this->getUserTagInfo();
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
        
        return $this->getUserTagInfo();
    }
    /**
     * Update or create a tag  ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $this->setDocument($resourceId);
        
        $err = $this->_document->addUTag(getCurrentUser()->id, $this->tagIdentifier, $this->contentParameters["tagValue"]);
        if ($err) {
            $exception = new Exception("CRUD0224", $this->tagIdentifier, $err);
            throw $exception;
        }
        return $this->getUserTagInfo();
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
        
        $userTag = $this->_document->getUTag($this->tagIdentifier, false);
        if (!$userTag) {
            $exception = new Exception("CRUD0223", $this->tagIdentifier);
            throw $exception;
        }
        $err = $this->_document->delUTag(getCurrentUser()->id, $this->tagIdentifier);
        if ($err) {
            $exception = new Exception("CRUD0224", $this->tagIdentifier, $err);
            throw $exception;
        }
        return null;
    }
    //endregion CRUD part
    protected function getUserTagInfo()
    {
        $info = array();
        
        $userTag = $this->_document->getUTag($this->tagIdentifier, false);
        
        if (!$userTag) {
            
            $exception = new Exception("CRUD0223", $this->tagIdentifier);
            $exception->setHttpStatus("404", "Not found");
            throw $exception;
        }
        /**
         * @var \DocUTag $userTag
         */
        
        $value = '';
        if ($userTag->comment) {
            if ($json = json_decode($userTag->comment)) {
                $value = $json;
            } else {
                $value = $userTag->comment;
            }
        }
        
        $tags = array(
            "id" => $userTag->tag,
            "date" => $userTag->date,
            "value" => $value
        );
        
        $info["uri"] = $this->generateURL(sprintf("%s/%s/usertags/%s", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid, $userTag->tag));
        
        $info["userTag"] = $tags;
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
        $this->tagIdentifier = $this->urlParameters["tagIdentifier"];
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
            "tagValue" => json_decode($jsonString)
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
