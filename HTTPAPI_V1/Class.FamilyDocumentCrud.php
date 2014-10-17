<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

use Dcp\HttpApi\V1\DocManager;
use \Dcp\HttpApi\V1\DocManager\Exception as DocManagerException;

class FamilyDocumentCrud extends DocumentCrud
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    //region CRUD part
    public function create()
    {
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
        
        $newValues = $this->contentParameters;
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
        $this->_document->addHistoryEntry(___("Create by HTTP API", "HTTPAPI_V1") , \DocHisto::NOTICE);
        DocManager::cache()->addDocument($this->_document);
        
        return $this->read($this->_document->id);
    }
    //endregion CRUD part
    
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
        $this->_family = DocManager::getFamily($familyId);
        if (!$this->_family) {
            $exception = new Exception("API0200", $familyId);
            $exception->setHttpStatus("404", "Family not found");
            throw $exception;
        }
    }
    /**
     * Set the document of the current request
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
        if (!is_a($this->_document, sprintf("\\Dcp\\Family\\%s", $this->_family->name))) {
            $e = new Exception("API0220", $resourceId, $this->_family->name);
            $e->setHttpStatus("404", "Document is not a document of the family " . $this->_family->name);
            throw $e;
        }
        if ($this->_document->doctype === "Z") {
            $e = new Exception("API0219", $resourceId);
            $e->setHttpStatus("404", "Document deleted");
            throw $e;
        }
    }
}
