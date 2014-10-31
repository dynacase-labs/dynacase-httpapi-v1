<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class History extends Crud
{
    /**
     * @var \Doc
     */
    protected $_document = null;
    /**
     * @var \DocFam
     */
    protected $_family = null;

    protected $slice = -1;

    protected $offset = 0;

    protected $revisionFilter = -1;
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

        $search = new \SearchDoc();
        $search->addFilter("initid = %d", $this->_document->initid);
        $search->setOrder("revision desc");
        $search->latest = false;
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
        $documentList = $search->search()->getDocumentList();

        $info["uri"] = $this->generateURL(
            sprintf("documents/%s/history/", $this->_document->name ? $this->_document->name : $this->_document->initid));
        $info["filters"] = array(
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
            }
            $revisionHistory[] = array(
                "documentId" => intval($revision->id),
                "uri" => $this->generateURL(
                    sprintf("documents/%s/revisions/%d.json",
                        ($revision->name ? $revision->name : $revision->initid), $revision->revision)),
                "title" => $revision->getTitle(),
                "fixed" => ($revision->locked == -1),
                "revision" => intval($revision->revision),
                "state" => $revision->getState(),
                "stateLabel" => ($revision->state) ? _($revision->state) : '',
                "stateColor" => ($revision->state) ? _($revision->getStateColor()) : '',
                "version" => $revision->version,
                "revisionDate" => strftime("%Y-%m-%d %T", $revision->revdate),
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
            $e->setURI($this->generateURL(sprintf("trash/%d.json", $this->_document->id)));
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
            simpleQuery(getDbAccess(), $sql, $result, false, true);
            $user = getCurrentUser();
            $result[] = $user->id;
            $result[] = $user->memberof;
            // Necessary for localized state label
            $result[] = \ApplicationParameterManager::getScopedParameterValue("CORE_LANG");
            return join("", $result);
        }
        return null;
    }
}
