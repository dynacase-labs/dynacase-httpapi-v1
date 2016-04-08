<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class WorkflowStateCollection extends Crud
{
    protected $baseURL = "documents";
    /**
     * @var \Doc
     */
    protected $_document = null;
    /**
     * @var \WDoc
     */
    protected $workflow = null;
    /**
     * @var \DocFam
     */
    protected $_family = null;
    /**
     * @var bool if true return all states else only followings
     */
    protected $allStates = false;
    //region CRUD part
    
    /**
     * Create new ressource
     *
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create state list");
        throw $exception;
    }
    /**
     * Get ressource
     *
     * @param string $resourceId Resource identifier
     *
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $this->setDocument($resourceId);
        
        $info = array();
        
        $baseUrl = $this->generateURL(sprintf("%s/%s/workflows/", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid));
        $info["uri"] = $baseUrl . "states/";
        
        $states = array();
        
        if ($this->allStates) {
            $wStates = $this->workflow->getStates();
        } else {
            $wStates = $this->workflow->getFollowingStates();
        }
        foreach ($wStates as $aState) {
            $transition = $this->workflow->getTransition($this->_document->state, $aState);
            if ($transition) {
                $controlTransitionError = $this->workflow->control($transition["id"]);
                $transitionData = array(
                    "id" => $transition["id"],
                    "uri" => sprintf("%stransitions/%s", $baseUrl, $transition["id"]) ,
                    "label" => _($transition["id"]) ,
                    "error" => $this->getM0($transition, $aState) ,
                    "authorized" => empty($controlTransitionError)
                );
            } else {
                $transitionData = null;
            }
            
            $state = $this->getStateInfo($aState);
            $state["uri"] = sprintf("%s%s", $info["uri"], $aState);
            
            $state["transition"] = $transitionData;
            
            $states[] = $state;
        }
        /**
         * @var \Doc $revision
         */
        
        $info["states"] = $states;
        return $info;
    }
    
    protected function getM0($tr, $state)
    {
        if ($tr && (!empty($tr["m0"]))) {
            // verify m0
            return call_user_func(array(
                $this->workflow,
                $tr["m0"],
            ) , $state, $this->workflow->doc->state);
        }
        return null;
    }
    /**
     * Update the ressource
     *
     * @param string $resourceId Resource identifier
     *
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot change state list");
        throw $exception;
    }
    /**
     * Delete ressource
     *
     * @param string $resourceId Resource identifier
     *
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot delete state list");
        throw $exception;
    }
    //endregion CRUD part
    
    /**
     * Find the current document and set it in the internal options
     *
     * @param $resourceId
     *
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
        $err = $this->_document->control("view");
        if ($err) {
            $exception = new Exception("CRUD0201", $resourceId, $err);
            $exception->setHttpStatus("403", "Forbidden");
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
        /**
         * @var \WDoc $workflow
         */
        $this->workflow = DocManager::getDocument($this->_document->wid);
        $this->workflow->set($this->_document);
    }
    /**
     * Set the family of the current request
     *
     * @param array $array
     *
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
     *
     * @param int $slice
     */
    public function setSlice($slice)
    {
        $this->slice = intval($slice);
    }
    /**
     * Set offset of revision to send
     *
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
        
        $this->allStates = !(empty($this->contentParameters["allStates"]));
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
                $sql = sprintf("select id, revdate from docread where id = %d", $doc->wid);
                simpleQuery(getDbAccess() , $sql, $result, false, true);
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
    
    protected function getStateInfo($state)
    {
        if (empty($state)) {
            return null;
        }
        return array(
            "id" => $state,
            "label" => _($state) ,
            "activity" => $this->workflow->getActivity($state) ,
            "displayValue" => ($this->workflow->getActivity($state)) ? $this->workflow->getActivity($state) : _($state) ,
            "color" => $this->workflow->getColor($state)
        );
    }
}
