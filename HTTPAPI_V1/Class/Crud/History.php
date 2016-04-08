<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class History extends Crud
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
        $exception->setHttpStatus("501", "Not yet implemented");
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
        
        $search = $this->prepareSearchDoc();
        $documentList = $search->getDocumentList();
        
        $info["uri"] = $this->generateURL(sprintf("%s/%s/history/", $this->baseURL, $this->_document->name ? $this->_document->name : $this->_document->initid));
        $info["requestParameters"] = array(
            "slice" => $this->slice,
            "offset" => $this->offset,
            "revision" => $this->revisionFilter
        );
        
        $revisionHistory = array();
        /**
         * @var \Doc $revision
         */
        foreach ($documentList as $revision) {
            $history = $revision->getHisto(false);
            foreach ($history as $k => $msg) {
                unset($history[$k]["id"]);
                unset($history[$k]["initid"]);
                $history[$k]["uid"] = intval($msg["uid"]);
                switch ($history[$k]["level"]) {
                    case \DocHisto::ERROR:
                        $history[$k]["level"] = "error";
                        break;

                    case \DocHisto::WARNING:
                        $history[$k]["level"] = "warning";
                        break;

                    case \DocHisto::MESSAGE:
                        $history[$k]["level"] = "message";
                        break;

                    case \DocHisto::INFO:
                        $history[$k]["level"] = "info";
                        break;

                    case \DocHisto::NOTICE:
                        $history[$k]["level"] = "notice";
                        break;
                }
            }
            $revisionHistory[] = array(
                "uri" => $this->generateURL(sprintf("%s/%s/revisions/%d.json", $this->baseURL, ($revision->name ? $revision->name : $revision->initid) , $revision->revision)) ,
                "properties" => array(
                    "id" => intval($revision->initid) ,
                    "title" => $revision->getTitle() ,
                    "status" => ($revision->doctype == "Z") ? "deleted" : (($revision->locked == - 1) ? "fixed" : "alive") ,
                    "revision" => intval($revision->revision) ,
                    "owner" => array(
                        "id" => $revision->owner,
                        "title" => \Account::getDisplayName($revision->owner)
                    ) ,
                    "state" => array(
                        "reference" => $revision->getState() ,
                        "stateLabel" => ($revision->state) ? _($revision->state) : '',
                        "activity" => ($revision->getStateActivity() ? _($revision->getStateActivity()) : ($revision->state ? _($revision->state) : '')) ,
                        "color" => ($revision->state) ? _($revision->getStateColor()) : ''
                    ) ,
                    
                    "version" => $revision->version,
                    "revisionDate" => strftime("%Y-%m-%d %T", $revision->revdate)
                ) ,
                "messages" => $history
            );
        }
        $info["history"] = $revisionHistory;
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
        $exception->setHttpStatus("405", "You cannot change history");
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
        $exception->setHttpStatus("405", "You cannot delete history");
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
        if (isset($this->contentParameters["revision"])) {
            $this->setRevisionFilter($this->contentParameters["revision"]);
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
            $id = DocManager::getIdentifier($id, true);
            $sql = sprintf("select id, date, comment from dochisto where id = %d order by date desc limit 1", $id);
            simpleQuery(getDbAccess() , $sql, $result, false, true);
            $user = getCurrentUser();
            $result[] = $user->id;
            $result[] = $user->memberof;
            // Necessary for localized state label
            $result[] = \ApplicationParameterManager::getScopedParameterValue("CORE_LANG");
            $result[] = \ApplicationParameterManager::getScopedParameterValue("WVERSION");
            return join("", $result);
        }
        return null;
    }
    /**
     * @return \SearchDoc
     */
    protected function prepareSearchDoc()
    {
        $search = new \SearchDoc();
        $search->addFilter("initid = %d", $this->_document->initid);
        $search->setOrder("revision desc");
        if ($this->revisionFilter >= 0) {
            $search->addFilter("revision = %d", $this->revisionFilter);
        }
        if ($this->slice > 0) {
            $search->setSlice($this->slice);
        }
        if ($this->offset > 0) {
            $search->setStart($this->offset);
        }
        $search->setObjectReturn();
        $search->latest = false;
        return $search;
    }
}
