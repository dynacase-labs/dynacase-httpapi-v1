<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
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
     * @var \DocFam
     */
    protected $_family = null;
    //region CRUD part
    
    /**
     * Create new ressource
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
        $info["uri"] = $baseUrl . "states/";
        
        $states = array();
        /**
         * @var \WDoc $workflow
         */
        $workflow = DocManager::getDocument($this->_document->wid);
        $workflow->set($this->_document);
        $wStates = $workflow->getFollowingStates();
        
        foreach ($wStates as $aState) {
            $transition = $workflow->getTransition($this->_document->state, $aState);
            if ($transition) {
                $transitionData = array(
                    "uri" => sprintf("%stransitions/%s", $baseUrl, $transition["id"]) ,
                    "label" => _($transition["id"])
                );
            } else {
                $transitionData = null;
            }
            
            $states[] = array(
                "uri" => sprintf("%s%s", $info["uri"], $aState) ,
                "transition" => $transitionData
            );
        }
        /**
         * @var \Doc $revision
         */
        
        $info["states"] = $states;
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
        $exception->setHttpStatus("405", "You cannot change state list");
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
        $exception->setHttpStatus("405", "You cannot delete state list");
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
                $result[] = $user->id;
                $result[] = $user->memberof;
                // Necessary for localized state label
                $result[] = \ApplicationParameterManager::getScopedParameterValue("CORE_LANG");
                return join("", $result);
            }
        }
        return null;
    }
}
