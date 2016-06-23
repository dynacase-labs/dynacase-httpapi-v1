<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class WorkflowState extends Crud
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
    /**
     * @var string workflow state asked
     */
    protected $state = null;
    /**
     * @var \WDoc
     */
    protected $workflow = null;
    /**
     * @var string|int
     */
    protected $documentId = 0;
    //region CRUD part
    
    /**
     * Change state
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $this->setDocument($this->documentId);
        $this->workflow = DocManager::getDocument($this->_document->wid);
        $this->workflow->set($this->_document);
        
        $this->workflow->disableEditControl();
        if (isset($this->contentParameters["parameters"]) && is_array($this->contentParameters["parameters"])) {
            foreach ($this->contentParameters["parameters"] as $aid => $value) {
                $this->workflow->setAttributeValue($aid, $value);
            }
        }
        $this->workflow->enableEditControl();
        
        $state = $this->getState();
        if (!$state) {
            $exception = new Exception("CRUD0235", $this->workflow->title, $this->workflow->id);
            $exception->setHttpStatus("404", "Invalid transition");
            throw $exception;
        }
        
        $err = $this->workflow->changeState($state, $this->contentParameters["comment"], $force = false, true, true, true, true, true, true, $message);
        if ($err) {
            $exception = new Exception("CRUD0230", $err);
            $exception->setHttpStatus("403", "Forbidden");
            $exception->setUserMessage($err);
            throw $exception;
        }
        if ($message) {
            $msg = new \Dcp\HttpApi\V1\Api\RecordReturnMessage();
            $msg->contentText = $message;
            $msg->type = \Dcp\HttpApi\V1\Api\RecordReturnMessage::WARNING;
            $msg->code = "WORKFLOW_TRANSITION";
            
            $this->addMessage($msg);
        }
        $info["state"] = $this->getStateInfo($this->_document->state);
        return $info;
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
        
        $baseUrl = $this->generateURL(sprintf("%s/%s/workflows/", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid));
        $info["uri"] = sprintf("%sstates/%s", $baseUrl, $this->getState());
        /**
         * @var \WDoc $workflow
         */
        $this->workflow = DocManager::getDocument($this->_document->wid);
        $this->workflow->set($this->_document);
        $allStates = $this->workflow->getStates();
        
        $state = isset($allStates[$this->getState() ]) ? $allStates[$this->getState() ] : null;
        if ($state === null) {
            $exception = new Exception("CRUD0228", $this->getState() , $this->workflow->title, $this->workflow->id);
            $exception->setHttpStatus("404", "State not found");
            throw $exception;
        }
        
        $transition = $this->workflow->getTransition($this->_document->state, $state);
        if ($transition) {
            
            $transitionData = array(
                "uri" => sprintf("%stransitions/%s", $baseUrl, $transition["id"]) ,
                "label" => _($transition["id"])
            );
        } else {
            $transitionData = null;
        }
        /**
         * @var \Doc $revision
         */
        
        $info["state"] = $this->getStateInfo($state);
        $info["state"]["transition"] = $transitionData;
        return $info;
    }
    
    protected function getStateInfo($state)
    {
        if (empty($state)) {
            return null;
        }
        return array(
            "id" => $state,
            "isCurrentState" => ($state === $this->_document->state) ,
            "label" => _($state) ,
            "activity" => $this->workflow->getActivity($state) ,
            "displayValue" => ($this->workflow->getActivity($state)) ? $this->workflow->getActivity($state) : _($state) ,
            "color" => $this->workflow->getColor($state)
        );
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
        $exception->setHttpStatus("405", "You cannot change state");
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
        $exception->setHttpStatus("405", "You cannot delete state");
        throw $exception;
    }
    //endregion CRUD part
    public function analyseJSON($jsonString)
    {
        $data = json_decode($jsonString, true);
        
        return array(
            "comment" => isset($data["comment"]) ? $data["comment"] : null,
            "parameters" => isset($data["parameters"]) ? $data["parameters"] : array()
        );
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
        
        if ($this->_document->wid == 0) {
            $exception = new Exception("CRUD0227", $resourceId);
            $exception->setHttpStatus("404", "No workflow detected");
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
        if (isset($this->urlParameters["state"])) {
            $this->state = $this->urlParameters["state"];
        }
        $this->documentId = $this->urlParameters["identifier"];
    }
    
    protected function getState()
    {
        return $this->state;
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
            $doc = DocManager::getDocument($id);
            if ($doc->wid > 0) {
                $sql = sprintf("select id, revdate from docread where id = %d or id = %d", $doc->wid, $doc->id);
                simpleQuery(getDbAccess() , $sql, $results, false, false);
                $result = array_merge(array_values($results[0]) , array_values($results[1]));
                
                $user = getCurrentUser();
                $result[] = $doc->state;
                $result[] = $user->id;
                $result[] = $user->memberof;
                // Necessary for localized state label
                $result[] = \ApplicationParameterManager::getScopedParameterValue("CORE_LANG");
                $result[] = \ApplicationParameterManager::getScopedParameterValue("WVERSION");
                return join("", $result);
            }
        }
        return null;
    }
}
