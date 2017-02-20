<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;
class DocumentFile extends Crud
{
    
    const CACHEIMGDIR = "var/cache/file/";
    
    private $tmpFlag = "_tmp_";
    /**
     * @var \Doc
     */
    protected $_document = null;
    /**
     * @var \DocFam
     */
    protected $_family = null;
    protected $inline = false;
    //region CRUD part
    
    
    /**
     * Gettag ressource
     *
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $fileInfo = $this->getFileInfo($resourceId);
        $cache = false;
        // No use cache when download original file from document
        FileUtils::downloadFile($fileInfo->path, $fileInfo->name, $fileInfo->mime_s, $this->inline, $cache);
        
        \Dcp\VaultManager::updateAccessDate($fileInfo->id_file);
        if ($fileInfo->id_file === $this->tmpFlag) {
            unlink($fileInfo->path);
        }
        exit;
    }
    /**
     * Create new tag ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create file");
        throw $exception;
    }
    /**
     * Update the ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot change file");
        throw $exception;
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot delete file");
        throw $exception;
    }
    public function getEtagInfo()
    {
        return null;
    }
    /**
     * @param string $resourceId
     *
     * @return \vaultFileInfo
     * @throws Exception
     */
    protected function getFileInfo($resourceId)
    {
        $this->setDocument($resourceId);
        
        $attrid = $this->urlParameters["attrid"];
        
        if ($attrid === "icon") {
            $fileValue = $this->_document->icon;
            $index = - 1;
        } else {
            $attribut = $this->_document->getAttribute($attrid);
            if (!$attribut) {
                throw new Exception("CRUD0605", $attrid, $this->_document->getTitle());
            }
            
            if (isset($this->urlParameters["index"])) {
                $index = intval($this->urlParameters["index"]);
            } else {
                if (!$attribut->isMultiple()) {
                    $index = - 1;
                } else {
                    $index = null;
                }
            }
            
            if ($attribut->mvisibility === "I") {
                $exception = new Exception("CRUD0606", $attrid, $this->_document->getTitle());
                $exception->setHttpStatus("403", "Forbidden");
                throw $exception;
            }
            
            if ($attribut->type !== "file" && $attribut->type !== "image") {
                throw new Exception("CRUD0614", $attrid, $resourceId);
            }
            
            $fileValue = $this->_document->getAttributeValue($attribut->id);
            
            if (is_array($fileValue) && $index === null) {
                return $this->zipFiles($attribut, $fileValue);
            }
        }
        
        if ($index === - 1 && is_array($fileValue)) {
            throw new Exception("CRUD0610", $index, $attrid, $resourceId);
        } elseif ($index >= 0 and !is_array($fileValue)) {
            throw new Exception("CRUD0611", $index, $attrid, $resourceId);
        } elseif ($index < - 1) {
            throw new Exception("CRUD0612", $index, $attrid, $resourceId);
        }
        
        if ($index >= 0) {
            $fileValue = $fileValue[$index];
        }
        if (empty($fileValue)) {
            $exception = new Exception("CRUD0607", $attrid, $index, $resourceId);
            $exception->setHttpStatus("404", "File not found");
            throw $exception;
        }
        
        preg_match(PREGEXPFILE, $fileValue, $reg);
        
        if (empty($reg["vid"])) {
            if ($attrid !== "icon") {
                throw new Exception("CRUD0609", $attrid, $index, $resourceId);
            } else {
                // Redirect to public icon
                $asset = new ImageAsset();
                $asset->setUrlParameters(["identifier" => $fileValue, "size" => $this->urlParameters["size"]]);
                $asset->execute("READ");
                return null;
            }
        }
        $vaultid = $reg["vid"];
        
        $fileInfo = \Dcp\VaultManager::getFileInfo($vaultid);
        if (!$fileInfo) {
            $exception = new Exception("CRUD0608", $attrid, $index, $resourceId);
            $exception->setHttpStatus("404", "File not found");
            throw $exception;
        }
        
        if (!empty($this->contentParameters["inline"])) {
            $inline = $this->contentParameters["inline"];
            $this->inline = ($inline === "yes" || $inline === "true" || $inline === "1");
        }
        return $fileInfo;
    }
    
    protected function zipFiles(\NormalAttribute $attribute, array $files)
    {
        $tmpZip = tempnam(getTmpDir() , "file" . $this->_document->id . "-") . ".zip";
        
        $zip = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE) === true) {
            $fileNamePattern = sprintf("%%0%dd-%%s", floor(log(count($files) , 10)) + 1);
            foreach ($files as $k => $file) {
                preg_match(PREGEXPFILE, $file, $reg);
                if (empty($reg["vid"])) {
                    throw new Exception("CRUD0609", $attribute->id, $k, $this->_document->id);
                }
                $fileInfo = \Dcp\VaultManager::getFileInfo($reg["vid"]);
                $zip->addFile($fileInfo->path, sprintf($fileNamePattern, $k + 1, $fileInfo->name));
            }
            $zip->close();
            $fileInfo = new \vaultFileInfo();
            $fileInfo->id_file = $this->tmpFlag;
            $fileInfo->name = $attribute->getLabel() . ".zip";
            $fileInfo->path = $tmpZip;
            $fileInfo->mime_s = "application/x-zip";
            return $fileInfo;
        } else {
            throw new Exception("CRUD0615", $attribute->id, "", $this->_document->id);
        }
    }
    /**
     * Find the current document and set it in the internal options
     *
     * @param $resourceId
     * @throws Exception
     */
    protected function setDocument($resourceId)
    {
        if (isset($this->urlParameters["revision"])) {
            $revisedId = DocManager::getRevisedDocumentId($resourceId, $this->urlParameters["revision"]);
            $this->_document = DocManager::getDocument($revisedId, false);
            if (!$this->_document) {
                $exception = new Exception("CRUD0221", $this->urlParameters["revision"], $resourceId);
                $exception->setHttpStatus("404", "Document not found");
                throw $exception;
            }
        } else {
            $this->_document = DocManager::getDocument($resourceId);
        }
        if (!$this->_document) {
            $exception = new Exception("CRUD0200", $resourceId);
            $exception->setHttpStatus("404", "Document not found");
            throw $exception;
        }
        
        $err = $this->_document->control("view");
        if ($err) {
            $exception = new Exception("CRUD0201", $resourceId, $err);
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
        
        if ($this->_family && !is_a($this->_document, sprintf("\\Dcp\\Family\\%s", $this->_family->name))) {
            $exception = new Exception("CRUD0220", $resourceId, $this->_family->name);
            $exception->setHttpStatus("404", "Document is not a document of the family " . $this->_family->name);
            throw $exception;
        }
        
        if ($this->_document->doctype === "Z") {
            $exception = new Exception("CRUD0219", $resourceId);
            $exception->setHttpStatus("404", "Document deleted");
            $exception->setURI($this->generateURL(sprintf("trash/%d.json", $this->_document->id)));
            throw $exception;
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
                $exception = new Exception("CRUD0200", $familyId);
                $exception->setHttpStatus("404", "Family not found");
                throw $exception;
            }
        }
    }
}
