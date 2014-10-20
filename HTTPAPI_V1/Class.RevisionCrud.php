<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\DocManager;

class RevisionCrud extends DocumentCrud
{
    /**
     * @var \DocFam
     */
    protected $_family = null;

    protected $slice = -1;

    protected $offset = 0;

    protected $revisionIdentifier = -1;
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
        $info = parent::read($resourceId);
        $info["revision"] = $info["document"];
        unset($info["document"]);
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

    public function execute($method, &$messages)
    {
        $this->initCrudParam();
        return parent::execute($method, $messages);
    }

    /**
     * Generate the default URI of the current ressource
     *
     * @return null|string
     */
    protected function getUri()
    {
        if ($this->_document) {

            if ($this->_document->doctype === "Z") {
                return sprintf("api/v1/trash/s/revisions/%d", $this->_document->name ? $this->_document->name : $this->_document->initid, $this->revisionIdentifier);
            } else {
                return sprintf("api/v1/documents/%s/revisions/%d", $this->_document->name ? $this->_document->name : $this->_document->initid, $this->revisionIdentifier);
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

        $this->_document = DocManager::getDocument($resourceId);

        if ($this->_document->revision != $this->revisionIdentifier) {
            $revisedId = DocManager::getRevisedDocumentId($this->_document->initid, $this->revisionIdentifier);
            $this->_document = DocManager::getDocument($revisedId, false);
        }

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

    protected function initCrudParam()
    {
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;
        if ($familyId !== false) {
            $this->_family = DocManager::getFamily($familyId);
            if (!$this->_family) {
                $exception = new Exception("API0200", $familyId);
                $exception->setHttpStatus("404", "Family not found");
                throw $exception;
            }
        }

        if (!empty($this->urlParameters["revision"])) {
            $this->revisionIdentifier = intval($this->urlParameters["revision"]);
        }
    }

    public function getEtagInfo()
    {
        if (isset($this->urlParameters["revision"]) && isset($this->urlParameters["identifier"])) {
            $id = DocManager::getRevisedDocumentId($this->urlParameters["identifier"], $this->urlParameters["revision"]);
            return $this->extractEtagDataFromId($id);
        }else {
            return parent::getEtagInfo();
        }
    }

}
