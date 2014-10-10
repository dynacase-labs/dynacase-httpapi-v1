<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\DocManager;
use \Dcp\HttpApi\V1\DocManager\Exception as DocManagerException;

class FamilyCrud extends DocumentCrud
{
    /**
     * @var \DocFam
     */
    protected $_family = null;

    //region CRUD part
    public function create()
    {
        $this->setFamily();
        try {
            $this->_document = DocManager::createDocument($this->_family->id);
        }
        catch(DocManagerException $exception) {
            if ($exception->getDcpCode() === "APIDM0003") {
                $exception = new Exception("API0204", $this->_family->name);
                $exception->setHttpStatus(403, "Forbidden");
                throw $exception;
            } else {
                throw $exception;
            }
        }
        
        $newValues = $this->getHttpAttributeValues();
        foreach ($newValues as $attrid => $value) {
            $err = $this->_document->setValue($attrid, $value);
            if ($err) {
                throw new Exception("API0205", $this->_family->name, $attrid, $err);
            }
        }
        
        $err = $this->_document->store($info);
        if ($err) {
            $exception = new Exception("API0206", $this->_family->name, $err);
            $exception->setData($info);
            throw $exception;
        }
        $this->_document->addHistoryEntry(___("Create by HTTP API", "httpapi") , \DocHisto::NOTICE);
        DocManager::cache()->addDocument($this->_document);
        
        return $this->read($this->_document->id);
    }

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

    protected function setFamily()
    {
        $familyId = $this->getRessourceIdentifier();

        $this->_family = DocManager::getFamily($familyId);
        if ($this->_family === null) {
            $exception = new Exception("API0203", $familyId);
            $exception->setHttpStatus(404, "Family not found");
            throw $exception;
        }
    }
}
