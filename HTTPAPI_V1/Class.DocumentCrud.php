<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\HttpApi\V1;

class DocumentCrud extends Crud
{
    /**
     * @var \Doc document instance
     */
    protected $_document = null;
    
    protected $defaultFields = "document.properties,document.attributes";
    protected $returnFields = null;
    protected $valueRender = array();
    protected $propRender = array();
    protected $fmtCollection = null;
    /**
     * @var int document icon width in px
     */
    public $iconSize = 32;
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
            $e = new Exception("API0201", $resourceId);
            $e->setUserMEssage(___("Update forbiden", "api"));
            $e->setHttpStatus("403", "Forbidden");
            throw $e;
        }
        
        if ($this->_document->doctype === 'C') {
            $e = new Exception("API0213", $this->_document->name);
            $e->setHttpStatus("403", "Forbidden");
            throw $e;
        }
        
        $newValues = $this->getHttpAttributeValues();
        foreach ($newValues as $aid => $value) {
            $kindex = - 1;
            if ($value === null or $value === '') {
                $err = $this->_document->clearValue($aid);
            } else {
                $err = $this->_document->setValue($aid, $value, -1, $kindex);
            }
            if ($err) {
                $e = new Exception("API0211", $this->_document->id, $aid, $err);
                $e->setUserMEssage(___("Update failed", "api"));
                $info = array(
                    "id" => $aid,
                    "index" => $kindex,
                    "err" => $err
                );
                
                $e->setData($info);
                throw $e;
            }
        }
        /**
         * @var \storeInfo $info
         */
        $err = $this->_document->store($info);
        if ($err) {
            $e = new Exception("API0212", $this->_document->id, $err);
            $e->setUserMEssage(___("Update failed", "api"));
            $e->setData($info);
            throw $e;
        }
        if ($info->refresh) {
            $message = new RecordReturnMessage();
            $message->contentText = ___("Document information", "api");
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
        $this->_document->addHistoryEntry(___("Updated by HTTP API", "httpapi") , \DocHisto::NOTICE);
        \Dcp\DocManager::cache()->addDocument($this->_document);
        
        return $this->get($this->_document->id);
    }
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $e = new Exception("API0002", __METHOD__);
        $e->setHttpStatus("501", "Not implemented");
        throw $e;
    }
    
    protected function setDocument($resourceId)
    {
        $this->_document = \Dcp\DocManager::getDocument($resourceId);
        if (!$this->_document) {
            $e = new Exception("API0200", $resourceId);
            $e->setHttpStatus("404", "Document not found");
            throw $e;
        }
    }
    
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
     * Get ressource
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function get($resourceId)
    {
        
        $this->setDocument($resourceId);
        $err = $this->_document->control("view");
        if ($err) {
            $e = new Exception("API0201", $resourceId, $err);
            $e->setHttpStatus("403", "Forbidden");
            throw $e;
        }
        if ($this->_document->mid == 0) {
        $this->_document->applyMask(\Doc::USEMASKCVVIEW);
        }
        return $this->documentData();
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
            $e = new Exception("API0216", $resourceId, $err);
            $e->setHttpStatus("403", "Forbidden");
            throw $e;
        }
        
        $err = $this->_document->delete();
        if ($err) {
            $e = new Exception("API0215", $this->_document->getTitle() , $err);
            throw $e;
        }
        $this->_document->addHistoryEntry(___("Deleted by HTTP API", "httpapi") , \DocHisto::NOTICE);
        return $this->documentData();
    }
    
    protected function getHttpAttributeValues()
    {
        if (preg_match('/(x-www-form-urlencoded|form-data)/', $_SERVER["CONTENT_TYPE"])) {
            return $this->getPostAttributeValues();
        } elseif (preg_match('/application\/json/', $_SERVER["CONTENT_TYPE"])) {
            
            return $this->getJSONAttributeValues();
        } else {
            throw new Exception("API0003", $_SERVER["CONTENT_TYPE"]);
        }
    }
    
    protected function getJSONAttributeValues()
    {
        $body = file_get_contents("php://input");
        $dataDocument = json_decode($body, true);
        if ($dataDocument === null) {
            throw new Exception("API0208", $body);
        }
        if (!isset($dataDocument["document"]["attributes"]) || !is_array($dataDocument["document"]["attributes"])) {
            
            throw new Exception("API0209", $body);
        }
        $values = $dataDocument["document"]["attributes"];
        
        $newValues = array();
        foreach ($values as $aid => $value) {
            if (!array_key_exists("value", $value) && is_array($value)) {
                $mulValues = array();
                foreach ($value as $singleValue) {
                    
                    if (!array_key_exists("value", $singleValue) && is_array($singleValue)) {
                        $mul2Values = array();
                        foreach ($singleValue as $secondVValue) {
                            $mul2Values[] = $secondVValue["value"];
                        }
                        $mulValues[] = $mul2Values;
                        //throw new Exception("API0217", $aid, print_r($value, true));
                        
                    } else {
                        $mulValues[] = $singleValue["value"];
                    }
                }
                $newValues[$aid] = $mulValues;
            } else {
                if (!array_key_exists("value", $value)) {
                    throw new Exception("API0210", $body);
                }
                $newValues[$aid] = $value["value"];
            }
        }
        return $newValues;
    }
    
    protected function getPostAttributeValues()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            parse_str(file_get_contents("php://input") , $_POST);
        }
        $newValues = array();
        foreach ($_POST as $aid => $value) {
            $newValues[strtolower($aid) ] = $value;
        }
        return $newValues;
    }
    
    protected function _getPropertiesId()
    {
        $defaultProperties = array(
            "title",
            "state",
            "name",
            "icon",
            "fromname",
            "fromtitle",
            "id",
            "initid",
            "postitid",
            "initid",
            "locked",
            "doctype",
            "revision",
            "wid",
            "cvid",
            "profid",
            "fromid",
            "owner",
            "domainid"
        );
        if ($this->hasFields("document.properties")) {
            return $defaultProperties;
        }
        $defaultProperties = array();
        $returnFields = $this->getFields();
        $subField = "document.property";
        foreach ($returnFields as $aField) {
            if (strpos($aField, $subField) === 0) {
                $defaultProperties[] = substr($aField, 18);
            }
        }
        return $defaultProperties;
    }
    
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

                    default:
                        $this->propRender[$propId] = $this->_document->getPropertyValue($propId);
                        if ($this->propRender[$propId] === false) {
                            throw new Exception("API0202", $propId);
                        }
                }
            }
        }
        return $this->propRender;
    }
    /**
     * @return \FormatCollection
     */
    protected function getFormatCollection()
    {
        if (!$this->fmtCollection) {
            $this->fmtCollection = new \FormatCollection($this->_document);
            // No comma / want root numbers
            $this->fmtCollection->setDecimalSeparator('.');
            $this->fmtCollection->mimeTypeIconSize = 20;
            $this->fmtCollection->useShowEmptyOption = false;
        }
        return $this->fmtCollection;
    }
    
    protected function _getAttributes()
    {
        

        if ($this->_document->doctype === "C") {
            return array();
        }
        if ($this->valueRender) {
            return $this->valueRender[0]["attributes"];
        }
        $dl = new \DocumentList();
        $dl->addDocumentIdentifiers(array(
            $this->_document->id
        ) , false);
        
        $fmtCollection = $this->getFormatCollection();
        $la = $this->_document->getNormalAttributes();
        foreach ($la as $aid => $attr) {
            if ($attr->type != "array" && $attr->mvisibility !== "I") {
                
                $fmtCollection->addAttribute($aid);
            }
        }
        $this->valueRender = $fmtCollection->render();
        $attributes = $this->valueRender[0]["attributes"];
        $nullValue = new \UnknowAttributeValue(null);
        foreach ($attributes as $k => $v) {
            if ($v === null) {
                $oa = $this->_document->getAttribute($k);
                if ($oa->isMultiple()) {
                    $attributes[$k] = array();
                } else {
                    $attributes[$k] = $nullValue;
                }
            }
        }
        return ($attributes);
    }
    
    protected function getUri()
    {
        if ($this->_document) {
            if ($this->_document->defDoctype === "C") {
                return sprintf("api/v1/families/%s.json", strtolower($this->_document->name));
            } else {
                if ($this->_document->doctype === "Z") {
                    
                    return sprintf("api/v1/trash/%d.json", $this->_document->id);
                } else {
                    return sprintf("api/v1/documents/%d.json", $this->_document->id);
                }
            }
        }
        return null;
    }
    
    protected function getFields()
    {
        if ($this->returnFields === null) {
            if (!empty($_GET["fields"])) {
                $fields = $_GET["fields"];
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
    
    protected function hasFields($fieldId, $subField = '')
    {
        
        $returnFields = $this->getFields();
        if (in_array($fieldId, $returnFields)) {
            return true;
        }
        
        if ($subField) {
            $subField.= ".";
            foreach ($returnFields as $aField) {
                if (strpos($aField, $subField) === 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    /**
     * Get document data
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
        if ($this->hasFields("document.properties", "document.property")) {
            $correctField = true;
            $conf["document"]["properties"] = $this->_getProperties();
        }
        
        if ($this->hasFields("document.attributes", "document.attribute")) {
            $correctField = true;
            $conf["document"]["attributes"] = $this->_getAttributes();
        }
        
        if ($this->hasFields("family.structure")) {
            $correctField = true;
            $conf["family"]["structure"] = $this->_getDocumentStructure();
        }
        
        if (!$correctField) {
            $fields = $this->getFields();
            if ($fields) {
                throw new Exception("API0214", implode(",", $fields));
            }
        }
        return $conf;
    }
    
    protected function _getDocumentStructure()
    {
        $la = $this->_document->getNormalAttributes();
        
        $t = array();
        $order = 0;
        foreach ($la as $oattr) {
            if ($oattr->type === "array" || $oattr->mvisibility === "I") {
                continue;
            }
            $parentAttr = $oattr->fieldSet;
            $parentIds = array();
            while ($parentAttr && $parentAttr->id != 'FIELD_HIDDENS') {
                $parentId = $parentAttr->id;
                $parentIds[] = $parentId;
                $parentAttr = $parentAttr->fieldSet;
            }
            $parentIds = array_reverse($parentIds);
            $previousId = null;
            unset($target);
            
            foreach ($parentIds as $aid) {
                if ($previousId === null) {
                    if (!isset($t[$aid])) {
                        $t[$aid] = $this->getAttributeInfo($this->_document->getAttribute($aid) , $order++);
                        $t[$aid]["content"] = array();
                    }
                    $target = & $t[$aid]["content"];
                } else {
                    if (!isset($target[$aid])) {
                        $target[$aid] = $this->getAttributeInfo($this->_document->getAttribute($aid) , $order++);
                        $target[$aid]["content"] = array();
                    }
                    $target = & $target[$aid]["content"];
                }
                $previousId = $aid;
            }
            $target[$oattr->id] = $this->getAttributeInfo($oattr, $order++);
        }
        return $t;
    }
    
    protected function getAttributeInfo(\BasicAttribute $oa, $order = 0)
    {
        
        $info = array(
            "id" => $oa->id,
            "visibility" => $oa->mvisibility,
            "label" => $oa->getLabel() ,
            "type" => $oa->type,
            "logicalOrder" => $order,
            "multiple" => $oa->isMultiple() ,
            "options" => $oa->getOptions()
        );
        
        if (isset($oa->needed)) {
            /**
             * @var \NormalAttribute $oa;
             */
            $info["needed"] = $oa->needed;
        }
        if (!empty($oa->phpfile) && $oa->type !== "enum") {
            /**
             * @var \NormalAttribute $oa;
             */
            if ((strlen($oa->phpfile) > 1) && ($oa->phpfunc)) {
                $oParse = new \parseFamilyFunction();
                $strucFunc = $oParse->parse($oa->phpfunc);
                foreach ($strucFunc->outputs as $k => $output) {
                    if (substr($output, 0, 2) === "CT") {
                        unset($strucFunc->outputs[$k]);
                    } else {
                        $strucFunc->outputs[$k] = strtolower($output);
                    }
                }
                $info["helpOutputs"] = $strucFunc->outputs;
            }
        }
        
        if ($oa->inArray()) {
            if ($this->_document->doctype === "C") {
                /**
                 * @var \DocFam $family
                 */
                $family = $this->_document;
                $defVal = $family->getDefValue($oa->id);
            } else {
                $defVal = $this->_document->getFamilyDocument()->getDefValue($oa->id);
            }
            $fmtDefValue = $this->getFormatCollection()->getInfo($oa, $defVal, $this->_document);
            if ($fmtDefValue) {
                if ($oa->isMultipleInArray()) {
                    foreach ($fmtDefValue as $aDefvalue) {
                        $info["defaultValue"][] = $aDefvalue[0];
                    }
                } else {
                    $info["defaultValue"] = $fmtDefValue[0];
                }
            }
        }
        
        if ($oa->type === "enum") {
            if ($oa->getOption("eformat") !== "auto") {
                $enums = $oa->getEnumLabel();
                $enumItems = array();
                foreach ($enums as $key => $label) {
                    $enumItems[] = array(
                        "key" => $key,
                        "label" => $label
                    );
                }
                $info["enumItems"] = $enumItems;
            }
            $info["enumUri"] = sprintf("api/v1/enums/%s/%s", $this->_document->fromname, $oa->id);
        }
        
        return $info;
    }
}
