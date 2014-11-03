<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Family extends Document
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
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create a document with an ID");
        throw $exception;
    }

    /**
     * Delete family
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot delete a family with the API");
        throw $exception;
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
            $exception = new Exception("CRUD0203", $familyId);
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
            $e = new Exception("CRUD0203", $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }
        if ($this->_document->doctype !== "C") {
            $e = new Exception("CRUD0203", $resourceId);
            $e->setHttpStatus("404", "Document is not a family");
            throw $e;
        }
        if ($this->_document->doctype === "Z") {
            $e = new Exception("CRUD0219", $resourceId);
            $e->setHttpStatus("404", "Document deleted");
            throw $e;
        }

    }

    public function checkId($identifier)
    {
        $familyName = $identifier;
        if (is_numeric($identifier)) {
            $familyName = DocManager::getNameFromId($identifier);
        }
        if ($familyName !== 0 && $familyName != $identifier) {
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is an id request for a family");
            $exception->addHeader("Location", $this->generateURL(sprintf("families/%s.json", $familyName), $query));
            $exception->setURI($this->generateURL(sprintf("families/%s.json", $familyName)));
            throw $exception;
        }
        return true;
    }
}
