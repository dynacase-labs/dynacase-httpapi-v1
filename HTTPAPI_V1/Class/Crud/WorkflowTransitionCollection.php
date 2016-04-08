<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class WorkflowTransitionCollection extends WorkflowStateCollection
{
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
        $info["uri"] = $baseUrl . "transitions/";
        
        $transitions = array();
        /**
         * @var \WDoc $workflow
         */
        $this->workflow = DocManager::getDocument($this->_document->wid);
        $this->workflow->set($this->_document);
        
        foreach ($this->workflow->transitions as $k => $transition) {
            
            $transitions[] = array(
                "uri" => sprintf("%s%s", $info["uri"], $k) ,
                "label" => _($k) ,
                "valid" => $this->isValidTransition($k)
            );
        }
        /**
         * @var \Doc $revision
         */
        
        $info["transitions"] = $transitions;
        return $info;
    }
    
    protected function isValidTransition($trId)
    {
        foreach ($this->workflow->cycle as $wTransition) {
            if (($wTransition["e1"] === $this->_document->state) && ($wTransition["t"] === $trId)) {
                return true;
            }
        }
        return false;
    }
}
