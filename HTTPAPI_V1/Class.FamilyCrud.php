<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class FamilyCrud extends DocumentCrud
{
    /**
     * @var \DocFam
     */
    protected $_family = null;
    
    protected function setFamily()
    {
        $familyId = $this->getRessourceIdentifier();
        
        $this->_family = \Dcp\HttpApi\V1\DocManager::getFamily($familyId);
        if ($this->_family === null) {
            $e = new Exception("API0203", $familyId);
            $e->setHttpStatus(404, "Family not found");
            throw $e;
        }
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
    
    public function create()
    {
        $this->setFamily();
        try {
            $this->_document = \Dcp\HttpApi\V1\DocManager::createDocument($this->_family->id);
        }
        catch(\Dcp\HttpApi\V1\DocManager\Exception $e) {
            if ($e->getDcpCode() === "APIDM0003") {
                $e = new Exception("API0204", $this->_family->name);
                $e->setHttpStatus(403, "Forbidden");
                throw $e;
            } else {
                throw $e;
            }
        }
        
        $newValues = $this->getHttpAttributeValues();
        foreach ($newValues as $aid => $value) {
            $err = $this->_document->setValue($aid, $value);
            if ($err) {
                throw new Exception("API0205", $this->_family->name, $aid, $err);
            }
        }
        
        $err = $this->_document->store($info);
        if ($err) {
            $e = new Exception("API0206", $this->_family->name, $err);
            $e->setData($info);
            throw $e;
        }
        $this->_document->addHistoryEntry(___("Create by HTTP API", "httpapi") , \DocHisto::NOTICE);
        \Dcp\HttpApi\V1\DocManager::cache()->addDocument($this->_document);
        
        return $this->get($this->_document->id);
    }
}
