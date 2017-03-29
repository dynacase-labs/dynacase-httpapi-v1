<?php
/*
 * @author Anakeen
 * @package FDL
*/
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 05/11/14
 * Time: 17:44
 */

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager;

class DocumentUtils
{
    /**
     * Check if the document id is valid
     *
     * Redurect to the canonical url if the id asked is a revision
     *
     * @param $identifier
     * @param string $canonicalURL
     * @return bool
     * @throws Exception
     */
    static public function checkDocumentId($identifier, $canonicalURL = "documents/%d.json")
    {
        $initid = $identifier;
        if (is_numeric($identifier)) {
            $initid = DocManager::getInitIdFromIdOrName($identifier);
        }
        if ($initid !== 0 && $initid != $identifier) {
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is a revision");
            $exception->addHeader("Location", URLUtils::generateURL(sprintf($canonicalURL, $initid) , $query));
            $exception->setURI(URLUtils::generateURL(sprintf($canonicalURL, $initid)));
            throw $exception;
        }
        return true;
    }
    /**
     * Analyze the content of a json request
     *
     * @param $jsonString
     * @return array
     * @throws Exception
     */
    static public function analyzeDocumentJSON($jsonString)
    {
        $dataDocument = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("CRUD0208", "Unable to json decode " . $jsonString);
        }
        if ($dataDocument === null) {
            throw new Exception("CRUD0208", $jsonString);
        }
        if (!isset($dataDocument["document"]["attributes"]) || !is_array($dataDocument["document"]["attributes"])) {
            throw new Exception("CRUD0209", $jsonString);
        }
        $values = $dataDocument["document"]["attributes"];
        
        $newValues = array();
        // Only keep the value element of each attribute passed
        foreach ($values as $attributeId => $value) {
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
                $newValues[$attributeId] = $multipleValues;
            } else {
                if (!is_array($value) || !array_key_exists("value", $value)) {
                    throw new Exception("CRUD0210", $jsonString);
                }
                $newValues[$attributeId] = $value["value"];
            }
        }
        return $newValues;
    }
    /**
     * Check if a required family exist
     * Redirect to the logical name def if the request use the id
     *
     * @param $identifier
     * @param string $urlReturn
     * @return bool
     * @throws Exception
     */
    static public function checkFamilyId($identifier, $urlReturn = "families/%s.json")
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
            $exception->addHeader("Location", URLUtils::generateURL(sprintf($urlReturn, $familyName) , $query));
            $exception->setURI(URLUtils::generateURL(sprintf($urlReturn, $familyName)));
            throw $exception;
        }
        return true;
    }
    /**
     * Analyze the list of required attributes
     *
     * @param \Doc $currentDoc
     * @param string $prefix
     * @param array $fields
     * @return array
     * @throws Exception
     */
    static public function getAttributesFields($currentDoc = null, $prefix = "document.attributes.", $fields = array())
    {
        $falseAttribute = array();
        // Compute the list of the attributes that should be displayed (if list is empty all will be displayed)
        $restrictedAttributes = array_filter($fields, function ($currentField) use ($prefix)
        {
            return mb_stripos($currentField, $prefix) === 0 && $currentField !== $prefix;
        });
        $restrictedAttributes = array_unique($restrictedAttributes);
        // end compute list
        // Analyze if all the restricted attributes as a part of the current doc or the current fam
        if ($currentDoc) {
            $restrictedAttributes = array_map(function ($currentField) use ($prefix, &$currentDoc, &$falseAttribute)
            {
                $attributeId = str_replace($prefix, "", $currentField);
                /* @var \Doc $currentDoc */
                self::isAttribute($currentDoc, $attributeId);
                return $attributeId;
            }
            , $restrictedAttributes);
        }
        // if there is attributes that not valid throw exception
        if (!empty($falseAttribute)) {
            throw new Exception("CRUD0218", join(" and attribute ", $falseAttribute));
        }
        $attributes = array();
        // compute the list
        if ($currentDoc) {
            // get all attributes without the restricted and I and array (if we have a ref doc)
            $normalAttributes = $currentDoc->getNormalAttributes();
            foreach ($normalAttributes as $attrId => $attribute) {
                if ($attribute->type != "array" && $attribute->mvisibility !== "I") {
                    if (!empty($restrictedAttributes) && !in_array($attrId, $restrictedAttributes)) {
                        continue;
                    }
                    $attributes[] = $attrId;
                }
            }
        } else {
            // if we don't have a ref doc just return the asked attributes list
            $attributes = array_map(function ($currentField) use ($prefix, &$currentDoc, &$falseAttribute)
            {
                return str_replace($prefix, "", $currentField);
            }
            , $restrictedAttributes);
        }
        return $attributes;
    }
    /**
     * Analyze the order by
     *
     * @param $orderBy
     * @param \Doc $currentDoc
     * @return string
     * @throws Exception
     */
    static public function extractOrderBy($orderBy, \Doc $currentDoc = null)
    {
        // Explode the string orderBy in an array
        $orderElements = explode(",", $orderBy);
        $result = array();
        $hasId = false;
        // Check for earch element if the property or attributes exist and the order to
        $propertiesList = array_keys(\Doc::$infofields);
        foreach ($orderElements as $currentElement) {
            $detectOrder = explode(":", $currentElement);
            
            $orderBy = $detectOrder[0];
            $orderDirection = isset($detectOrder[1]) ? mb_strtolower($detectOrder[1]) : "asc";
            if ($orderDirection !== "asc" && $orderDirection !== "desc") {
                throw new Exception("CRUD0501", $orderDirection);
            }
            if (!in_array($orderBy, $propertiesList) && !self::isAttribute($currentDoc, $orderBy)) {
                throw new Exception("CRUD0506", $orderBy);
            }
            if ($orderBy === "id") {
                $hasId = true;
            }
            $result[] = sprintf("%s %s", pg_escape_string($orderBy) , $orderDirection);
        }
        // if the id is not asked add it (for avoid double result in slice)
        if (!$hasId) {
            $result[] = sprintf("id desc");
        }
        return implode(", ", $result);
    }
    /**
     * Check if an attrid is an attribute of the currentDoc
     *
     * @param \Doc $currentDoc
     * @param $currentElement
     * @return bool
     * @throws Exception
     */
    protected static function isAttribute(\Doc $currentDoc, $currentElement)
    {
        if ($currentDoc) {
            $currentAttribute = $currentDoc->getAttribute($currentElement);
            if ($currentAttribute === false || $currentAttribute->type === "frame" || $currentAttribute->type === "array" || $currentAttribute->type === "tab" || $currentAttribute->type === "menu" || $currentAttribute->usefor === "Q" || $currentAttribute->mvisibility === "I") {
                if ($currentAttribute) {
                    /**
                     * @var \BasicAttribute $currentAttribute
                     */
                    if ($currentAttribute->mvisibility === "I") {
                        throw new Exception("CRUD0508", $currentElement, $currentAttribute->getLabel());
                    }
                    throw new Exception("CRUD0507", $currentElement, $currentAttribute->getLabel() , $currentAttribute->type);
                } else {
                    throw new Exception("CRUD0502", $currentElement);
                }
            }
        }
        return true;
    }
}
