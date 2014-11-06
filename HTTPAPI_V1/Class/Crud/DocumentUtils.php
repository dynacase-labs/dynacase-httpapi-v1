<?php
/**
 * Created by PhpStorm.
 * User: charles
 * Date: 05/11/14
 * Time: 17:44
 */

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager;

class DocumentUtils {

    static public function checkDocumentId($identifier, $canonicalURL = "documents/%d.json") {
        $initid = $identifier;
        if (is_numeric($identifier)) {
            $initid = DocManager::getInitIdFromIdOrName($identifier);
        }
        if ($initid !== 0 && $initid != $identifier) {
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is a revision");
            $exception->addHeader("Location", URLUtils::generateURL(sprintf($canonicalURL, $initid), $query));
            $exception->setURI(URLUtils::generateURL(sprintf($canonicalURL, $initid)));
            throw $exception;
        }
        return true;
    }

    static public function analyzeDocumentJSON($jsonString) {
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

    static public function checkFamilyId($identifier, $urlReturn = "families/%s.json") {
        $familyName = $identifier;
        if (is_numeric($identifier)) {
            $familyName = DocManager::getNameFromId($identifier);
        }
        if ($familyName !== 0 && $familyName != $identifier) {
            $pathInfo = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query = parse_url($pathInfo, PHP_URL_QUERY);
            $exception = new Exception("CRUD0222");
            $exception->setHttpStatus("307", "This is an id request for a family");
            $exception->addHeader("Location", URLUtils::generateURL(sprintf($urlReturn, $familyName), $query));
            $exception->setURI(URLUtils::generateURL(sprintf($urlReturn, $familyName)));
            throw $exception;
        }
        return true;
    }

    static public function getAttributesFields(\Doc $currentDoc = null, $prefix = "document.attributes.", $fields = array()) {
        $falseAttribute = array();
        $restrictedAttributes = array_filter($fields, function ($currentField) use ($prefix) {
            return mb_stripos($currentField, $prefix) === 0 && $currentField !== $prefix;
        });
        $restrictedAttributes = array_unique($restrictedAttributes);
        $restrictedAttributes = array_map(function ($currentField) use ($prefix, &$currentDoc, &$falseAttribute) {
                $attributeId = str_replace($prefix, "", $currentField);
                /* @var \Doc $currentDoc */
                self::isAttribute($currentDoc, $attributeId);
                return $attributeId;
            }
            , $restrictedAttributes);
        if (!empty($falseAttribute)) {
            throw new Exception("CRUD0218", join(" and attribute ", $falseAttribute));
        }
        $attributes = array();
        if ($currentDoc) {
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
            $attributes = $restrictedAttributes;
        }
        return $attributes;
    }

    static public function extractOrderBy($orderBy, \Doc $currentDoc = null) {
        $orderElements = explode(",", $orderBy);
        $result = array();
        $hasId = false;
        $propertiesList = array_keys(\Doc::$infofields);
        foreach ($orderElements as $currentElement) {
            $detectOrder = explode(":", $currentElement);
            $orderBy = pg_escape_string($detectOrder[0]);
            $orderDirection = isset($detectOrder[1]) ? mb_strtolower($detectOrder[1]) : "asc";
            if ($orderDirection !== "asc" && $orderDirection !== "desc") {
                throw new Exception("CRUD0501", $orderDirection);
            }
            if (!in_array($orderBy, $propertiesList) && !self::isAttribute($currentDoc, $currentElement)) {
                throw new Exception("CRUD0502", $orderBy);
            }
            if ($orderBy === "id") {
                $hasId = true;
            }
            $result[] = sprintf("%s %s", $orderBy, $orderDirection);
        }
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
            if ($currentAttribute === false
                || $currentAttribute->type === "frame"
                || $currentAttribute->type === "array"
                || $currentAttribute->type === "tab"
                || $currentAttribute->type === "menu"
                || $currentAttribute->usefor === "Q"
                || $currentAttribute->mvisibility === "I"
            ) {
                throw new Exception("CRUD0502", $currentElement);
            }
        }
        return true;
    }


} 