<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\DocManager;

class FamilyCrud extends DocumentCrud
{
    /**
     * @var \DocFam
     */
    protected $_family = null;

    //region CRUD part

    /**
     * Update a family
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
     * Delete family
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
     * Set the current family
     *
     * @throws Exception
     */
    protected function setFamily()
    {
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;

        $this->_family = DocManager::getFamily($familyId);
        if ($this->_family === null) {
            $exception = new Exception("API0203", $familyId);
            $exception->setHttpStatus(404, "Family not found");
            throw $exception;
        }
    }

    /**
     * Set the current document
     *
     * @param $resourceId
     * @throws Exception
     */
    protected function setDocument($resourceId)
    {
        $this->_document = DocManager::getDocument($resourceId);
        if (!$this->_document) {
            $e = new Exception("API0203", $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }
        if ($this->_document->doctype !== "C") {
            $e = new Exception("API0203", $resourceId);
            $e->setHttpStatus("404", "Document is not a family");
            throw $e;
        }
        if ($this->_document->doctype === "Z") {
            $e = new Exception("API0219", $resourceId);
            $e->setHttpStatus("404", "Document deleted");
            throw $e;
        }

    }
}
