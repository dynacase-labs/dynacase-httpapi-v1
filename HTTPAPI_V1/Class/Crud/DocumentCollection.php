<?php
/*
 * @author Anakeen
 * @package FDL
*/
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 03/11/14
 * Time: 14:12
 */

namespace Dcp\HttpApi\V1\Crud;

class DocumentCollection extends Crud
{
    
    const GET_PROPERTIES = "document.properties";
    const GET_PROPERTY = "document.properties.";
    const GET_ATTRIBUTES = "document.attributes";
    const GET_ATTRIBUTE = "document.attributes.";
    
    protected $defaultFields = null;
    protected $returnFields = null;
    protected $slice = 0;
    protected $offset = 0;
    protected $orderBy = "";
    /**
     * @var \SearchDoc
     */
    protected $_searchDoc = null;
    
    public function __construct()
    {
        parent::__construct();
        $this->defaultFields = self::GET_PROPERTIES;
    }
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You need to use the family collection to create document");
        throw $exception;
    }
    /**
     * Read a ressource
     * @param string|int $resourceId Resource identifier
     * @return mixed
     */
    public function read($resourceId)
    {
        $documentList = $this->prepareDocumentList();
        $return = array(
            "requestParameters" => array(
                "slice" => $this->slice,
                "offset" => $this->offset,
                "length" => count($documentList) ,
                "orderBy" => $this->orderBy
            )
        );
        
        $return["uri"] = $this->generateURL("documents/");
        $return["properties"] = $this->getCollectionProperties();
        $documentFormatter = $this->prepareDocumentFormatter($documentList);
        $data = $documentFormatter->format();
        $return["documents"] = $data;
        
        return $return;
    }
    /**
     * Update the ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function update($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot update all the documents");
        throw $exception;
    }
    /**
     * Delete ressource
     * @param string|int $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function delete($resourceId)
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("405", "You cannot delete all the documents.");
        throw $exception;
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
        $fields = $this->getFields();
        if ($this->hasFields(self::GET_ATTRIBUTE)) {
            return DocumentUtils::getAttributesFields(null, $prefix, $fields);
        }
        return array();
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
     * Prepare the searchDoc
     * You can inherit of this function to make specialized collection (trash, search, etc...)
     */
    protected function prepareSearchDoc()
    {
        $this->_searchDoc = new \SearchDoc();
        $this->_searchDoc->setObjectReturn();
        $this->_searchDoc->excludeConfidential(true);
    }
    /**
     * Analyze the slice, offset and sortBy
     *
     * @return \DocumentList
     */
    public function prepareDocumentList()
    {
        $this->prepareSearchDoc();
        $this->slice = isset($this->contentParameters["slice"]) ? mb_strtolower($this->contentParameters["slice"]) : \Dcp\HttpApi\V1\Api\Router::getHttpApiParameter("COLLECTION_DEFAULT_SLICE");
        if ($this->slice !== "all") {
            $this->slice = intval($this->slice);
        }
        $this->_searchDoc->setSlice($this->slice);
        $this->offset = isset($this->contentParameters["offset"]) ? $this->contentParameters["offset"] : 0;
        $this->offset = intval($this->offset);
        $this->_searchDoc->setStart($this->offset);
        $this->orderBy = $this->extractOrderBy();
        $this->_searchDoc->setOrder($this->orderBy);
        return $this->_searchDoc->getDocumentList();
    }
    
    protected function getCollectionProperties()
    {
        return array(
            "title" => ""
        );
    }
    /**
     * Extract orderBy
     *
     * @return string
     * @throws Exception
     */
    protected function extractOrderBy()
    {
        $orderBy = isset($this->contentParameters["orderBy"]) ? $this->contentParameters["orderBy"] : "title:asc";
        return DocumentUtils::extractOrderBy($orderBy);
    }
    /**
     * Initialize the document formatter
     * Extract the properties and attributes
     *
     * @param $documentList
     * @return DocumentFormatter
     * @throws \Dcp\HttpApi\V1\DocManager\Exception
     */
    protected function prepareDocumentFormatter($documentList)
    {
        $documentFormatter = new DocumentFormatter($documentList);
        if ($this->hasFields(self::GET_PROPERTIES, true) && !$this->hasFields(self::GET_PROPERTY)) {
            $documentFormatter->useDefaultProperties();
        } else {
            $documentFormatter->setProperties($this->_getPropertiesId() , $this->hasFields(self::GET_PROPERTIES, true));
        }
        $documentFormatter->setAttributes($this->getAttributeFields());
        return $documentFormatter;
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
}
