<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Revision extends Document
{
    /**
     * @var \DocFam
     */
    protected $_family = null;

    protected $slice = -1;

    protected $offset = 0;

    protected $revisionIdentifier = null;
    //region CRUD part

    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $e = new Exception("CRUD0103", __METHOD__);
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
        parent::setDocument($resourceId);
        $info = array();
        if ($this->revisionIdentifier === null) {
            $search = new \SearchDoc();
            $search->setObjectReturn(true);
            $search->addFilter("initid = %d", $this->_document->initid);
            $search->setOrder("revision desc");
            $search->latest = false;
            foreach ($search->getDocumentList() as $currentRevision) {
                $info[] = array(
                    "title" => $currentRevision->getTitle(),
                    "documentId" => intval($currentRevision->id),
                    "uri" => $this->generateURL($this->getUri($currentRevision, $currentRevision->revision))
                );
            }

        } else {
            $info = parent::read($resourceId);
            $info["revision"] = $info["document"];
            unset($info["document"]);
        }
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
        $e = new Exception("CRUD0103", __METHOD__);
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
        $e = new Exception("CRUD0103", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }

    //endregion CRUD part

    public function execute($method, array & $messages = array())
    {
        $this->initCrudParam();
        return parent::execute($method, $messages);
    }

    /**
     * Generate the default URI of the current ressource
     *
     * @param null $document
     * @param null $revisionIdentifier identifier of the revision
     *
     * @return null|string
     */
    protected function getUri($document = null, $revisionIdentifier = null)
    {
        if ($document === null) {
            $document = $this->_document;
        }
        if ($revisionIdentifier === null) {
            $revisionIdentifier = $this->revisionIdentifier;
        }
        $id = $document->name ? $document->name : $document->initid;
        if ($document) {
            if ($document->doctype === "Z") {
                return $this->generateURL(sprintf("trash/%s/revisions/%d.json", $id, $revisionIdentifier));
            } else {
                return $this->generateURL(sprintf("documents/%s/revisions/%d.json", $id, $revisionIdentifier));
            }
        }
        return null;
    }

    /**
     * Find the current document and set it in the internal options
     *
     * @param $resourceId
     * @throws Exception
     */
    protected function setDocument($resourceId)
    {

        if ($this->_document->revision !== "" && $this->_document->revision != $this->revisionIdentifier) {
            $revisedId = DocManager::getRevisedDocumentId($this->_document->initid, $this->revisionIdentifier);
            $this->_document = DocManager::getDocument($revisedId, false);
        }

        if (!$this->_document) {
            $e = new Exception("CRUD0221", $this->revisionIdentifier, $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }

        if ($this->_family && !is_a($this->_document, sprintf("\\Dcp\\Family\\%s", $this->_family->name))) {
            $e = new Exception("CRUD0220", $resourceId, $this->_family->name);
            $e->setHttpStatus("404", "Document is not a document of the family " . $this->_family->name);
            throw $e;
        }

        if ($this->_document->doctype === "Z") {
            $e = new Exception("CRUD0219", $resourceId);
            $e->setHttpStatus("404", "Document deleted");
            $e->setURI($this->generateURL(sprintf("trash/%d.json", $this->_document->id)));
            throw $e;
        }
    }

    /**
     * Init some internal params with extracted params
     *
     * @throws Exception
     */
    protected function initCrudParam()
    {
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;
        if ($familyId !== false) {
            $this->_family = DocManager::getFamily($familyId);
            if (!$this->_family) {
                $exception = new Exception("CRUD0200", $familyId);
                $exception->setHttpStatus("404", "Family not found");
                throw $exception;
            }
        }

        if ($this->urlParameters["revision"] !== "") {
            $this->revisionIdentifier = intval($this->urlParameters["revision"]);
        }
    }

    /**
     * Generate Etag for the current revision
     *
     * @return null|string
     */
    public function getEtagInfo()
    {
        if (isset($this->urlParameters["revision"])
            && isset($this->urlParameters["identifier"])
            && $this->urlParameters["revision"] !== ""
            && $this->urlParameters["identifier"] !== ""
        ) {
            $id = $this->urlParameters["identifier"];
            if (!is_numeric($id)) {
                $id = DocManager::getIdFromName($this->urlParameters["identifier"]);
                $this->urlParameters["identifier"] = $id;
            }
            $id = DocManager::getRevisedDocumentId($id, $this->urlParameters["revision"]);
            return $this->extractEtagDataFromId($id);
        } else {
            return parent::getEtagInfo();
        }
    }

    public function checkId($identifier)
    {
        $initid = $identifier;
        if (is_numeric($identifier)) {
            $initid = DocManager::getInitIdFromIdOrName($identifier);
        }
        if ($initid !== 0 && $initid != $identifier) {
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is a revision");
            $exception->addHeader("Location", $this->generateURL(sprintf("documents/%d/revisions/%d.json", $initid, $this->urlParameters["revision"]), $query));
            $exception->setURI($this->generateURL(sprintf("documents/%d/revisions/%d.json", $initid, $this->urlParameters["revision"])));
            throw $exception;
        }
        return true;
    }

}
