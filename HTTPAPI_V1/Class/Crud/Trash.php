<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Trash extends Document {

    //region CRUD part
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create() {
        $exception = new Exception("CRUD0103", "create");
        $exception->setHttpStatus("405");
        throw $exception;
    }

    /**
     * Update the ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId) {
        $exception = new Exception("CRUD0103", "update");
        $exception->setHttpStatus("405");
        throw $exception;
    }

    /**
     * Delete ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId) {
        $exception = new Exception("CRUD0103", "delete");
        $exception->setHttpStatus("405");
        throw $exception;
    }
    //endregion CRUD part
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
            $e = new Exception("CRUD0200", $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }
        if ($this->_document->doctype !== "Z") {
            $e = new Exception("CRUD0219", $resourceId);
            $e->setHttpStatus("404", "Document not in the trash");
            throw $e;
        }

    }
} 