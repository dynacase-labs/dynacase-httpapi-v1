<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class WorkflowTransition extends WorkflowState
{
    
    protected $transition = null;
    /**
     * @var \WDoc
     */
    protected $workflow = null;
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
        $info["uri"] = sprintf("%stransitions/%s", $baseUrl, $this->transition);
        
        $this->workflow = DocManager::getDocument($this->_document->wid);
        $this->workflow->set($this->_document);
        
        $transition = isset($this->workflow->transitions[$this->transition]) ? $this->workflow->transitions[$this->transition] : null;
        
        if ($transition === null) {
            $exception = new Exception("CRUD0229", $this->transition, $this->workflow->title, $this->workflow->id);
            $exception->setHttpStatus("404", "Transition not found");
            throw $exception;
        }
        
        $nextState = '';
        foreach ($this->workflow->cycle as $wTransition) {
            if (($wTransition["e1"] === $this->_document->state) && ($wTransition["t"] === $this->transition)) {
                $nextState = $wTransition["e2"];
            }
        }
        /**
         * @var \Doc $revision
         */
        
        $info["transition"] = array(
            "id" => $this->transition,
            "beginState" => $this->getStateInfo($this->_document->state) ,
            "endState" => $this->getStateInfo($nextState) ,
            "label" => _($this->transition) ,
            "askComment" => empty($transition["nr"]) ,
            "askAttributes" => $this->getAskAttributes(isset($transition["ask"]) ? $transition["ask"] : array())
        );
        return $info;
    }
    
    protected function getState()
    {
        foreach ($this->workflow->cycle as $wTransition) {
            if (($wTransition["e1"] === $this->_document->state) && ($wTransition["t"] === $this->transition)) {
                return $wTransition["e2"];
            }
        }
    }
    protected function getAskAttributes($askes)
    {
        if (empty($askes)) {
            return array();
        }
        $workflow = new Document($this->workflow);
        
        $attrData = array();
        foreach ($askes as $ask) {
            $oa = $this->workflow->getAttribute($ask);
            if ($oa) {
                $attrData[] = $workflow->getAttributeInfo($oa);
            }
        }
        return $attrData;
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
        
        $this->transition = $this->urlParameters["transition"];
    }
}
