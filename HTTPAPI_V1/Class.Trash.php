<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 10/10/14
 * Time: 18:31
 */

namespace Dcp\HttpApi\V1;


class Trash extends DocumentCrud {


    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create() {
        $exception = new Exception("API0103", "create");
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
        $exception = new Exception("API0103", "update");
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
        $exception = new Exception("API0103", "delete");
        $exception->setHttpStatus("405");
        throw $exception;
    }

    protected function setDocument($resourceId)
    {
        $this->_document = DocManager::getDocument($resourceId);
        if (!$this->_document) {
            $e = new Exception("API0200", $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }
        if ($this->_document->doctype !== "Z") {
            $e = new Exception("API0219", $resourceId);
            $e->setHttpStatus("404", "Document not in the trash");
            throw $e;
        }

    }
} 