<?php
/*
 * @author Anakeen
 * @package FDL
*/
namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;
use Dcp\HttpApi\V1\Api\RecordReturnMessage as RecordReturnMessage;

class Document extends Crud
{
    
    const GET_PROPERTIES = "document.properties";
    const GET_PROPERTY = "document.properties.";
    const GET_ATTRIBUTES = "document.attributes";
    const GET_ATTRIBUTE = "document.attributes.";
    const GET_STRUCTURE = "family.structure";
    /**
     * @var \Doc document instance
     */
    protected $_document = null;
    
    protected $defaultFields = null;
    protected $returnFields = null;
    protected $valueRender = array();
    protected $propRender = array();
    /**
     * @var DocumentFormatter
     */
    protected $documentFormater = null;
    /**
     * @var int document icon width in px
     */
    public $iconSize = 32;
    
    public function __construct(\Doc $document = null)
    {
        parent::__construct();
        if ($document !== null) {
            $this->_document = $document;
        }
        $this->defaultFields = self::GET_PROPERTIES . "," . self::GET_ATTRIBUTES;
    }
    //region CRUD part
    
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot create a document with an ID");
        throw $exception;
    }
    /**
     * Get ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId)
    {
        $this->setDocument($resourceId);
        $err = $this->_document->control("view");
        if (!$err) {
            if ($this->_document->isConfidential()) {
                $err = "Confidential document";
            }
        }
        if ($err) {
            $exception = new Exception("CRUD0201", $resourceId, $err);
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
        if ($this->_document->mid == 0) {
            $this->_document->applyMask(\Doc::USEMASKCVVIEW);
        }
        return $this->getDocumentData();
    }
    /**
     * Update the ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $this->setDocument($resourceId);
        
        $err = $this->_document->canEdit();
        if ($err) {
            $exception = new Exception("CRUD0201", $resourceId, $err);
            $exception->setUserMEssage(___("Update forbidden", "HTTPAPI_V1"));
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
        
        if ($this->_document->doctype === 'C') {
            $exception = new Exception("CRUD0213", $this->_document->name);
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
        
        $newValues = $this->contentParameters;
        foreach ($newValues as $aid => $value) {
            try {
                if ($value === null or $value === '') {
                    $this->_document->setAttributeValue($aid, null);
                } else {
                    $this->_document->setAttributeValue($aid, $value);
                }
            }
            catch(\Dcp\AttributeValue\Exception $e) {
                $exception = new Exception("CRUD0211", $this->_document->id, $aid, $e->getDcpMessage());
                $exception->setHttpStatus("500", "Unable to modify the document");
                $exception->setUserMEssage(___("Update failed", "HTTPAPI_V1"));
                $info = array(
                    "id" => $aid,
                    "index" => $e->index,
                    "err" => $e->originalError ? $e->originalError : $e->getDcpMessage()
                );
                
                $exception->setData($info);
                throw $exception;
            }
        }
        
        $this->renameFileNames();
        /**
         * @var \storeInfo $info
         */
        $err = $this->_document->store($info);
        if ($err) {
            $exception = new Exception("CRUD0212", $this->_document->id, $err);
            $exception->setHttpStatus("500", "Unable to modify the document");
            $exception->setUserMEssage(___("Update failed", "HTTPAPI_V1"));
            $exception->setData($info);
            throw $exception;
        }
        if ($info->refresh) {
            $message = new RecordReturnMessage();
            $message->contentText = ___("Document information", "HTTPAPI_V1");
            $message->contentHtml = $info->refresh;
            $message->type = $message::MESSAGE;
            $message->code = "refresh";
            $this->addMessage($message);
        }
        if ($info->postStore) {
            $message = new RecordReturnMessage();
            $message->contentText = $info->postStore;
            $message->type = $message::MESSAGE;
            $message->code = "store";
            $this->addMessage($message);
        }
        $this->_document->addHistoryEntry(___("Updated by HTTP API", "HTTPAPI_V1") , \DocHisto::NOTICE);
        DocManager::cache()->addDocument($this->_document);
        
        return $this->read($this->_document->initid);
    }
    /**
     * Delete ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $this->setDocument($resourceId);
        
        $err = $this->_document->control("delete");
        if ($err) {
            $exception = new Exception("CRUD0216", $resourceId, $err);
            $exception->setHttpStatus("403", "Forbidden");
            throw $exception;
        }
        
        $err = $this->_document->delete();
        if ($err) {
            $exception = new Exception("CRUD0215", $this->_document->getTitle() , $err);
            throw $exception;
        }
        $this->_document->addHistoryEntry(___("Deleted by HTTP API", "HTTPAPI_V1") , \DocHisto::NOTICE);
        return $this->getDocumentData();
    }
    //endregion CRUD part
    public function execute($method, array & $messages = array() , &$httpStatus = "")
    {
        $identifier = isset($this->urlParameters["identifier"]) ? $this->urlParameters["identifier"] : null;
        $this->checkId($identifier);
        return parent::execute($method, $messages, $httpStatus);
    }
    /**
     * Find the current document and set it in the internal options
     *
     * @param $ressourceId string|int identifier of the document
     * @throws Exception
     */
    protected function setDocument($ressourceId)
    {
        $this->_document = DocManager::getDocument($ressourceId);
        if (!$this->_document) {
            $exception = new Exception("CRUD0200", $ressourceId);
            $exception->setHttpStatus("404", "Document not found");
            throw $exception;
        }
        if ($this->_document->doctype === "Z") {
            $exception = new Exception("CRUD0219", $ressourceId);
            $exception->setHttpStatus("404", "Document deleted");
            $exception->setURI($this->generateURL(sprintf("trash/%d.json", $this->_document->initid)));
            throw $exception;
        }
    }
    /**
     * Initialize the default fields
     *
     * @param $fields
     * @return $this
     */
    public function setDefaultFields($fields)
    {
        $this->returnFields = null;
        $this->defaultFields = $fields;
        return $this;
    }
    /**
     * Get data from document object
     * No access control are done
     * @param \Doc $document Document
     * @throws Exception
     * @return mixed
     */
    public function getInternal(\Doc $document)
    {
        $this->_document = $document;
        return $this->getDocumentData();
    }
    /**
     * Honor "rn" file option
     * Rename file names if a new file is loaded.
     */
    protected function renameFileNames()
    {
        $fa = $this->_document->GetFileAttributes();
        foreach ($fa as $aid => $oa) {
            $rn = $oa->getOption("rn");
            $ov = $this->_document->getOldRawValue($aid);
            if ($rn && $ov !== false && $ov !== $this->_document->getRawValue($aid)) {
                $this->_document->refreshRn();
                return;
            }
        }
    }
    /**
     * Get the list of the properties required
     *
     * @return array
     */
    protected function _getPropertiesId()
    {
        $properties = array();
        $returnFields = $this->getFields();
        $subField = self::GET_PROPERTY;
        foreach ($returnFields as $currentField) {
            if (strpos($currentField, $subField) === 0) {
                $properties[] = substr($currentField, mb_strlen(self::GET_PROPERTY));
            }
        }
        return $properties;
    }
    /**
     * Get the attributes values
     *
     * @return mixed
     * @throws \Dcp\Fmtc\Exception
     */
    protected function _getAttributes()
    {
        
        if ($this->_document->doctype === "C") {
            return array();
        }
        
        return DocumentUtils::getAttributesFields($this->_document, self::GET_ATTRIBUTE, $this->getFields());
    }
    /**
     * Get the restrict fields value
     *
     * The restrict fields is used for restrict the return of the get request
     *
     * @return array|null
     */
    protected function getFields()
    {
        if ($this->returnFields === null) {
            if (!empty($this->contentParameters["fields"])) {
                $fields = $this->contentParameters["fields"];
            } else {
                $fields = $this->defaultFields;
            }
            if ($fields) {
                $this->returnFields = array_map("trim", explode(",", $fields));
            } else {
                $this->returnFields = array();
            }
        }
        return $this->returnFields;
    }
    /**
     * Check if the current restrict field exist
     *
     * @param string $fieldId field
     * @param boolean $strict strict test
     *
     * @return bool
     */
    protected function hasFields($fieldId, $strict = false)
    {
        $returnFields = $this->getFields();
        
        if (!$strict) {
            foreach ($returnFields as $aField) {
                if (strpos($aField, $fieldId) === 0) {
                    return true;
                }
            }
        } else {
            if (in_array($fieldId, $returnFields)) {
                return true;
            }
        }
        
        return false;
    }
    /**
     * Get document data
     *
     * @throws Exception
     * @return string
     */
    protected function getDocumentData()
    {
        $return = array();
        $this->documentFormater = new DocumentFormatter($this->_document);
        $correctField = false;
        $hasProperties = false;
        
        if ($this->hasFields(self::GET_PROPERTIES, true) && !$this->hasFields(self::GET_PROPERTY)) {
            $correctField = true;
            $hasProperties = true;
            $this->documentFormater->useDefaultProperties();
        } elseif ($this->hasFields(self::GET_PROPERTY)) {
            $correctField = true;
            $hasProperties = true;
            $this->documentFormater->setProperties($this->_getPropertiesId() , $this->hasFields(self::GET_PROPERTIES, true));
        }
        
        if ($this->hasFields(self::GET_ATTRIBUTES)) {
            $correctField = true;
            $this->documentFormater->setAttributes($this->_getAttributes());
        }
        
        $return["document"] = $this->documentFormater->format() [0];
        
        if (!$hasProperties) {
            unset($return["document"]["properties"]);
        }
        
        if ($this->hasFields(self::GET_STRUCTURE)) {
            $correctField = true;
            $return["family"]["structure"] = $this->_getDocumentStructure();
        }
        
        if (!$correctField) {
            $fields = $this->getFields();
            if ($fields) {
                throw new Exception("CRUD0214", implode(",", $fields));
            }
        }
        return $return;
    }
    /**
     * Generate the structure of the document
     *
     * @return array
     */
    protected function _getDocumentStructure()
    {
        $normalAttributes = $this->_document->getNormalAttributes();
        
        $return = array();
        $order = 0;
        foreach ($normalAttributes as $attribute) {
            if ($attribute->type === "array") {
                continue;
            }
            $parentAttribute = $attribute->fieldSet;
            $parentIds = array();
            while ($parentAttribute && $parentAttribute->id != 'FIELD_HIDDENS') {
                $parentId = $parentAttribute->id;
                $parentIds[] = $parentId;
                $parentAttribute = $parentAttribute->fieldSet;
            }
            $parentIds = array_reverse($parentIds);
            $previousId = null;
            unset($target);
            
            foreach ($parentIds as $aid) {
                if ($previousId === null) {
                    if (!isset($return[$aid])) {
                        $return[$aid] = $this->getAttributeInfo($this->_document->getAttribute($aid) , $order++);
                        $return[$aid]["content"] = array();
                    }
                    $target = & $return[$aid]["content"];
                } else {
                    if (!isset($target[$aid])) {
                        $target[$aid] = $this->getAttributeInfo($this->_document->getAttribute($aid) , $order++);
                        $target[$aid]["content"] = array();
                    }
                    $target = & $target[$aid]["content"];
                }
                $previousId = $aid;
            }
            $target[$attribute->id] = $this->getAttributeInfo($attribute, $order++);
        }
        return $return;
    }
    /**
     * Get the attribute info
     *
     * @param \BasicAttribute $attribute
     * @param int $order
     * @return array
     */
    public function getAttributeInfo(\BasicAttribute $attribute, $order = 0)
    {
        $info = array(
            "id" => $attribute->id,
            "visibility" => ($attribute->mvisibility) ? $attribute->mvisibility : $attribute->visibility,
            "label" => $attribute->getLabel() ,
            "type" => $attribute->type,
            "logicalOrder" => $order,
            "multiple" => $attribute->isMultiple() ,
            "options" => $attribute->getOptions()
        );
        
        if (isset($attribute->needed)) {
            /**
             * @var \NormalAttribute $attribute ;
             */
            $info["needed"] = $attribute->needed;
        }
        if (!empty($attribute->phpfile) && $attribute->type !== "enum") {
            /**
             * @var \NormalAttribute $attribute ;
             */
            if ((strlen($attribute->phpfile) > 1) && ($attribute->phpfunc)) {
                $familyParser = new \ParseFamilyFunction();
                $structureFunction = $familyParser->parse($attribute->phpfunc);
                foreach ($structureFunction->outputs as $k => $output) {
                    if (substr($output, 0, 2) === "CT") {
                        unset($structureFunction->outputs[$k]);
                    } else {
                        $structureFunction->outputs[$k] = strtolower($output);
                    }
                }
                $info["helpOutputs"] = $structureFunction->outputs;
            }
        }
        
        if ($attribute->inArray()) {
            if ($this->_document->doctype === "C") {
                /**
                 * @var \DocFam $family
                 */
                $family = $this->_document;
                $defaultValue = $family->getDefValue($attribute->id);
            } else {
                $defaultValue = $this->_document->getFamilyDocument()->getDefValue($attribute->id);
            }
            if ($defaultValue) {
                $defaultValue = $this->_document->applyMethod($defaultValue, $defaultValue);
            }
            
            $formatDefaultValue = $this->documentFormater->getFormatCollection()->getInfo($attribute, $defaultValue, $this->_document);
            
            if ($formatDefaultValue) {
                if ($attribute->isMultipleInArray()) {
                    foreach ($formatDefaultValue as $aDefvalue) {
                        $info["defaultValue"][] = $aDefvalue[0];
                    }
                } else {
                    $info["defaultValue"] = $formatDefaultValue[0];
                }
            }
        }
        
        if ($attribute->type === "enum") {
            if ($attribute->getOption("eformat") !== "auto") {
                $enums = $attribute->getEnumLabel();
                $enumItems = array();
                foreach ($enums as $key => $label) {
                    $enumItems[] = array(
                        "key" => (string)$key,
                        "label" => $label
                    );
                }
                $info["enumItems"] = $enumItems;
            }
            $url=sprintf("families/%s/enumerates/%s", ($this->_document->doctype === "C" ? $this->_document->name : $this->_document->fromname) , $attribute->id);
            $info["enumUrl"] = "api/v1/".$url ;
            $info["enumUri"] = $this->generateURL($url);
        }
        
        return $info;
    }
    /**
     * Return etag info
     *
     * @return null|string
     */
    public function getEtagInfo()
    {
        if (isset($this->urlParameters["identifier"])) {
            $id = $this->urlParameters["identifier"];
            $id = DocManager::getIdentifier($id, true);
            return $this->extractEtagDataFromId($id);
        }
        return null;
    }
    /**
     * Compute etag from an id
     *
     * @param $id
     *
     * @return string
     * @throws \Dcp\Db\Exception
     */
    protected function extractEtagDataFromId($id)
    {
        $result = array();
        $sql = sprintf("select id, revdate, views from docread where id = %d", $id);
        simpleQuery(getDbAccess() , $sql, $result, false, true);
        $user = getCurrentUser();
        $result[] = $user->id;
        $result[] = $user->memberof;
        // Necessary only when use family.structure
        $result[] = \ApplicationParameterManager::getScopedParameterValue("CORE_LANG");
        $result[] = \ApplicationParameterManager::getScopedParameterValue("WVERSION");
        return join(" ", $result);
    }
    /**
     * Analyze JSON string and extract update values
     *
     * @param $jsonString
     * @return array
     * @throws Exception
     */
    public function analyseJSON($jsonString)
    {
        return DocumentUtils::analyzeDocumentJSON($jsonString);
    }
    /**
     * Check is the ID is canonical and redirect if not
     *
     * @param $identifier
     * @return bool
     * @throws Exception
     */
    public function checkId($identifier)
    {
        return DocumentUtils::checkDocumentId($identifier);
    }
}
