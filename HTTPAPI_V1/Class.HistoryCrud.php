<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\DocManager;

class HistoryCrud extends Crud
{
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
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
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
            $e = new Exception("API0201", $resourceId, $err);
            $e->setHttpStatus("403", "Forbidden");
            throw $e;
        }
        
        $info = array();
        
        $s = new \SearchDoc();
        $s->addFilter("initid = %d", $this->_document->initid);
        $s->setOrder("revision desc");
        $s->latest = false;
        if ($this->revisionFilter >= 0) {
            $s->addFilter("revision = %d", $this->revisionFilter);
        }
        if ($this->slice > 0) {
            $s->setSlice($this->slice);
        }
        if ($this->offset > 0) {
            $s->setStart($this->offset);
        }
        $s->setObjectReturn();
        $dl = $s->search()->getDocumentList();
        
        $info["uri"] = sprintf("api/v1/documents/%s/history/", $this->_document->name ? $this->_document->name : $this->_document->initid);
        $info["filters"] = array(
            "slice" => $this->slice,
            "offset" => $this->offset,
            "revision" => $this->revisionFilter
        );
        
        $revisionHistory = array();
        /**
         * @var \Doc $revision
         */
        foreach ($dl as $revision) {
            
            $history = $revision->getHisto(false);
            foreach ($history as $k => $msg) {
                unset($history[$k]["id"]);
                unset($history[$k]["initid"]);
                $history[$k]["uid"] = intval($msg["uid"]);
            }
            $revisionHistory[] = array(
                "documentId" => intval($revision->id) ,
                "uri" => sprintf("api/v1/documents/%s/revisions/%d", ($revision->name ? $revision->name : $revision->initid) , $revision->revision) ,
                "title" => $revision->getTitle() ,
                "fixed" => ($revision->locked == - 1) ,
                "revision" => intval($revision->revision) ,
                "state" => $revision->getState() ,
                "stateLabel" => ($revision->state) ? _($revision->state) : '',
                "stateColor" => ($revision->state) ? _($revision->getStateColor()) : '',
                "version" => $revision->version,
                "revisionDate" => strftime("%Y-%m-%d %T", $revision->revdate) ,
                "messages" => $history
            );
        }
        $info["history"] = $revisionHistory;
        /*
        if ($this->revisionFilter >=0 && count($dl)===0) {
            // Verify if it a as proble of permission
            $revision=DocManager::getRevisedDocumentId($this->_document->initid, $this->revisionFilter);
            if ($revision !== false) => 403 Forbidden ?
        }*/
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
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
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
            $e = new Exception("API0200", $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }
        
        if ($this->_family && !is_a($this->_document, sprintf("\\Dcp\\Family\\%s", $this->_family->name))) {
            $e = new Exception("API0220", $resourceId, $this->_family->name);
            $e->setHttpStatus("404", "Document is not a document of the family " . $this->_family->name);
            throw $e;
        }
        
        if ($this->_document->doctype === "Z") {
            $e = new Exception("API0219", $resourceId);
            $e->setHttpStatus("404", "Document deleted");
            $e->setURI(sprintf("api/v1/trash/%d.json", $this->_document->id));
            throw $e;
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
                $exception = new Exception("API0200", $familyId);
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
     * To return history of a specific revision
     * @param int $revisionFilter
     */
    public function setRevisionFilter($revisionFilter)
    {
        $this->revisionFilter = intval($revisionFilter);
    }
    /**
     * Analyze the parameters of the request
     *
     * @throws Exception
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
        if (isset($this->contentParameters["revision"])) {
            $this->setRevisionFilter($this->contentParameters["revision"]);
        }
    }
    public function getEtagInfo()
    {
        if (isset($this->urlParameters["identifier"])) {
            $id = $this->urlParameters["identifier"];
            if (!is_numeric($id)) {
                $id = getIdFromName(getDbAccess() , $id);
            }
            $sql = sprintf("select id, date, comment from dochisto where id = %d order by date desc limit 1", $id);
            simpleQuery(getDbAccess() , $sql, $result, false, true);
            $u = getCurrentUser();
            $result[] = $u->id;
            $result[] = $u->memberof;
            // Necessary for localized state label
            $result[] = \ApplicationParameterManager::getScopedParameterValue("CORE_LANG");
            return join("", $result);
        }
        return null;
    }
}
