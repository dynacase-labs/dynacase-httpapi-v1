<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
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
    protected $formatCollection = null;
    /**
     * @var int document icon width in px
     */
    public $iconSize = 32;
    
    public function __construct()
    {
        parent::__construct();
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
        $e = new Exception("CRUD0103", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
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
        if ($err) {
            $e = new Exception("CRUD0201", $resourceId, $err);
            $e->setHttpStatus("403", "Forbidden");
            throw $e;
        }
        if ($this->_document->mid == 0) {
            $this->_document->applyMask(\Doc::USEMASKCVVIEW);
        }
        return $this->documentData();
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
            $kindex = - 1;
            if ($value === null or $value === '') {
                $err = $this->_document->clearValue($aid);
            } else {
                $err = $this->_document->setValue($aid, $value, -1, $kindex);
            }
            if ($err) {
                $exception = new Exception("CRUD0211", $this->_document->id, $aid, $err);
                $exception->setHttpStatus("500", "Unable to modify the document");
                $exception->setUserMEssage(___("Update failed", "HTTPAPI_V1"));
                $info = array(
                    "id" => $aid,
                    "index" => $kindex,
                    "err" => $err
                );
                
                $exception->setData($info);
                throw $exception;
            }
        }
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
            $e = new Exception("CRUD0216", $resourceId, $err);
            $e->setHttpStatus("403", "Forbidden");
            throw $e;
        }
        
        $err = $this->_document->delete();
        if ($err) {
            $e = new Exception("CRUD0215", $this->_document->getTitle() , $err);
            throw $e;
        }
        $this->_document->addHistoryEntry(___("Deleted by HTTP API", "HTTPAPI_V1") , \DocHisto::NOTICE);
        return $this->documentData();
    }
    //endregion CRUD part
    public function execute($method, array & $messages = array()) {
        $identifier = isset($this->urlParameters["identifier"]) ? $this->urlParameters["identifier"] : null;
        $this->checkId($identifier);
        return parent::execute($method, $messages);
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
        return $this->documentData();
    }
    /**
     * Get the list of the properties required
     *
     * @return array
     */
    protected function _getPropertiesId()
    {
        $defaultProperties = array(
            "cvid",
            "doctype",
            "fromid",
            "fromname",
            "fromtitle",
            "icon",
            "id",
            "initid",
            "locked",
            "name",
            "owner",
            "postitid",
            "profid",
            "revision",
            "state",
            "title",
            "wid",
        );
        if ($this->hasFields(self::GET_PROPERTIES)) {
            return $defaultProperties;
        }
        $defaultProperties = array();
        $returnFields = $this->getFields();
        $subField = self::GET_PROPERTY;
        foreach ($returnFields as $aField) {
            if (strpos($aField, $subField) === 0) {
                $defaultProperties[] = substr($aField, mb_strlen(self::GET_PROPERTY));
            }
        }
        return $defaultProperties;
    }
    /**
     * Return the array of properties of the current doc
     *
     * @return array
     * @throws Exception
     */
    protected function _getProperties()
    {
        
        if ($this->propRender) {
            return $this->propRender;
        }
        
        if ($this->_document) {
            $propIds = $this->_getPropertiesId();
            foreach ($propIds as $propId) {
                switch ($propId) {
                    case "revision":
                    case "locked":
                    case "initid":
                    case "wid":
                    case "cvid":
                    case "lockdomainid":
                    case "profid":
                    case "fromid":
                    case "owner":
                    case "id":
                        $this->propRender[$propId] = intval($this->_document->getPropertyValue($propId));
                        break;

                    case "icon":
                        $this->propRender[$propId] = $this->_document->getIcon("", $this->iconSize);
                        break;

                    case "title":
                        $this->propRender[$propId] = $this->_document->getTitle();
                        break;

                    case "fromtitle":
                        $famTitle = '';
                        if ($this->_document->fromid > 0) {
                            $fam = $this->_document->getFamilyDocument();
                            $famTitle = $fam->getTitle();
                        }
                        $this->propRender[$propId] = $famTitle;
                        break;

                    case "readonly":
                        if ($this->_document->id > 0) {
                            $this->propRender[$propId] = ($this->_document->canEdit() != "");
                        }
                        break;

                    case "revdate":
                        $this->propRender[$propId] = strftime("%Y-%m-%d %H:%M:%S", $this->_document->revdate);
                        break;

                    case "labelstate":
                        $this->propRender[$propId] = $this->_document->state ? _($this->_document->state) : '';
                        break;

                    case "postitid":
                        $this->propRender[$propId] = $this->_document->rawValueToArray($this->_document->getPropertyValue($propId));
                        break;

                    case "fromname":
                        $this->propRender[$propId] = $this->_document->fromname;
                        break;

                    default:
                        $this->propRender[$propId] = $this->_document->getPropertyValue($propId);
                        if ($this->propRender[$propId] === false) {
                            throw new Exception("CRUD0202", $propId);
                        }
                }
            }
        }
        return $this->propRender;
    }
    /**
     * Initialize, cache and return a format collection object
     *
     * @return \FormatCollection
     */
    protected function getFormatCollection()
    {
        if (!$this->formatCollection) {
            $this->formatCollection = new \FormatCollection($this->_document);
            // No comma / want root numbers
            $this->formatCollection->setDecimalSeparator('.');
            $this->formatCollection->mimeTypeIconSize = 20;
            $this->formatCollection->useShowEmptyOption = false;
        }
        return $this->formatCollection;
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
        if ($this->valueRender) {
            return $this->valueRender[0]["attributes"];
        }

        $formatCollection = $this->getFormatCollection();
        $normalAttributes = $this->_document->getNormalAttributes();
        $filteredAttributes = $this->getAttributeFields();
        foreach ($normalAttributes as $attrId => $attribute) {
            if ($attribute->type != "array" && $attribute->mvisibility !== "I") {
                if (!empty($filteredAttributes) && !in_array($attrId, $filteredAttributes)) {
                    continue;
                }
                $formatCollection->addAttribute($attrId);
            }
        }
        $this->valueRender = $formatCollection->render();
        $attributes = $this->valueRender[0]["attributes"];
        $nullValue = new \UnknowAttributeValue(null);
        if (!empty($attributes)) {
            foreach ($attributes as $attrid => $value) {
                if ($value === null) {
                    $objectAttribute = $this->_document->getAttribute($attrid);
                    if ($objectAttribute->isMultiple()) {
                        $attributes[$attrid] = array();
                    } else {
                        $attributes[$attrid] = $nullValue;
                    }
                }
            }
        }
        return ($attributes);
    }
    /**
     * Generate the default URI of the current ressource
     *
     * @return null|string
     */
    protected function getUri()
    {
        if ($this->_document) {
            if ($this->_document->defDoctype === "C") {
                return $this->generateURL(sprintf("families/%s.json", $this->_document->name));
            } else {
                if ($this->_document->doctype === "Z") {
                    return $this->generateURL(sprintf("trash/%s.json", $this->_document->name ? $this->_document->name : $this->_document->initid));
                } else {
                    return $this->generateURL(sprintf("documents/%s.json", $this->_document->name ? $this->_document->name : $this->_document->initid));
                }
            }
        }
        return null;
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
     * @param $fieldId
     * @param string $subField
     * @return bool
     */
    protected function hasFields($fieldId, $subField = '')
    {
        $returnFields = $this->getFields();
        if (in_array($fieldId, $returnFields)) {
            return true;
        }
        
        if ($subField) {
            foreach ($returnFields as $aField) {
                if (strpos($aField, $subField) === 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    /**
     * Get the restricted attributes
     *
     * @throws Exception
     * @return array
     */
    protected function getAttributeFields()
    {
        $prefix = self::GET_ATTRIBUTE;
        $currentDoc = $this->_document;
        $fields = $this->getFields();
        $falseAttribute = array();
        $attributes = array_filter($fields, function ($currentField) use ($prefix)
        {
            return mb_stripos($currentField, $prefix) === 0 && $currentField !== $prefix;
        });
        $attributes = array_unique($attributes);
        $attributes = array_map(function ($currentField) use ($prefix, &$currentDoc, &$falseAttribute)
        {
            $attributeId = str_replace($prefix, "", $currentField);
            /* @var \Doc $currentDoc */
            if ($currentDoc->getAttribute($attributeId) === false) {
                $falseAttribute[] = $attributeId;
            }
            return $attributeId;
        }
        , $attributes);
        if (!empty($falseAttribute)) {
            throw new Exception("CRUD0218", join(" and attribute ", $falseAttribute));
        }
        return $attributes;
    }
    /**
     * Get document data
     *
     * @throws Exception
     * @return string
     */
    protected function documentData()
    {
        $conf = array(
            "document" => array(
                "uri" => $this->getUri() ,
            )
        );
        $correctField = false;
        if ($this->hasFields(self::GET_PROPERTY, self::GET_PROPERTIES)) {
            $correctField = true;
            $conf["document"]["properties"] = $this->_getProperties();
        }
        
        if ($this->hasFields(self::GET_ATTRIBUTES, self::GET_ATTRIBUTE)) {
            $correctField = true;
            $conf["document"]["attributes"] = $this->_getAttributes();
        }
        
        if ($this->hasFields(self::GET_STRUCTURE)) {
            $correctField = true;
            $conf["family"]["structure"] = $this->_getDocumentStructure();
        }
        
        if (!$correctField) {
            $fields = $this->getFields();
            if ($fields) {
                throw new Exception("CRUD0214", implode(",", $fields));
            }
        }
        return $conf;
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
            if ($attribute->type === "array" || $attribute->mvisibility === "I") {
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
    protected function getAttributeInfo(\BasicAttribute $attribute, $order = 0)
    {
        $info = array(
            "id" => $attribute->id,
            "visibility" => $attribute->mvisibility,
            "label" => $attribute->getLabel() ,
            "type" => $attribute->type,
            "logicalOrder" => $order,
            "multiple" => $attribute->isMultiple() ,
            "options" => $attribute->getOptions()
        );
        
        if (isset($attribute->needed)) {
            /**
             * @var \NormalAttribute $attribute;
             */
            $info["needed"] = $attribute->needed;
        }
        if (!empty($attribute->phpfile) && $attribute->type !== "enum") {
            /**
             * @var \NormalAttribute $attribute;
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
            $formatDefaultValue = $this->getFormatCollection()->getInfo($attribute, $defaultValue, $this->_document);
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
                        "key" => $key,
                        "label" => $label
                    );
                }
                $info["enumItems"] = $enumItems;
            }
            $info["enumUri"] = $this->generateURL(sprintf("families/%s/enumerates/%s", $this->_document->fromname, $attribute->id));
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
        simpleQuery(getDbAccess(), $sql, $result, false, true);
        $user = getCurrentUser();
        $result[] = $user->id;
        $result[] = $user->memberof;
        // Necessary only when use family.structure
        $result[] = \ApplicationParameterManager::getScopedParameterValue("CORE_LANG");
        return join(" ", $result);
    }

    /**
     * Analyze JSON string and extract update values
     *
     * @param $jsonString
     * @return array
     * @throws Exception
     */
    public function analyseJSON($jsonString) {
        $dataDocument = json_decode($jsonString, true);
        if ($dataDocument === null) {
            throw new Exception("CRUD0208", $jsonString);
        }
        if (!isset($dataDocument["document"]["attributes"]) || !is_array($dataDocument["document"]["attributes"])) {
            throw new Exception("CRUD0209", $jsonString);
        }
        $values = $dataDocument["document"]["attributes"];

        $newValues = array();
        foreach ($values as $aid => $value) {
            if (is_array($value) && !array_key_exists("value", $value)) {
                $multipleValues = array();
                foreach ($value as $singleValue) {
                    if (is_array($singleValue) && !array_key_exists("value", $singleValue)) {
                        $multipleSecondLevelValues = array();
                        foreach ($singleValue as $secondVValue) {
                            $multipleSecondLevelValues[] = $secondVValue["value"];
                        }
                        $multipleValues[] = $multipleSecondLevelValues;
                    } else {
                        $multipleValues[] = $singleValue["value"];
                    }
                }
                $newValues[$aid] = $multipleValues;
            } else {
                if (!is_array($value) || !array_key_exists("value", $value)) {
                    throw new Exception("CRUD0210", $jsonString);
                }
                $newValues[$aid] = $value["value"];
            }
        }
        return $newValues;
    }

    public function checkId($identifier) {
        $initid = $identifier;
        if (is_numeric($identifier)) {
            $initid = DocManager::getInitIdFromIdOrName($identifier);
        }
        if ($initid !== 0 && $initid != $identifier) {
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is a revision");
            $exception->addHeader("Location", $this->generateURL(sprintf("documents/%d.json", $initid), $query));
            $exception->setURI($this->generateURL(sprintf("documents/%d.json", $initid)));
            throw $exception;
        }
        return true;
    }
}
