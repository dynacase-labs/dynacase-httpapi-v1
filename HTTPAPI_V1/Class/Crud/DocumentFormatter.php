<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\Exception;

/**
 * Class DocumentFormatter
 * This class is a facade of FormatCollection (had format for REST collection)
 *
 * @package Dcp\HttpApi\V1\Crud
 */
class DocumentFormatter
{
    
    static protected $uselessProperties = array(
        "lockdomainid",
        "domainid",
        "svalues",
        "ldapdn",
        "comment",
        "classname"
    );
    /* @var \FormatCollection $formatCollection */
    protected $formatCollection;
    protected $defaultProperties = array(
        "initid",
        "title",
        "revision",
        "state",
        "icon",
        "name"
    );
    protected $properties = array();
    protected $generateUrl = null;
    
    public function __construct($source)
    {
        if (is_a($source, "Doc")) {
            /* if the $source is a doc, we want to render only one document*/
            $this->formatCollection = new \FormatCollection($source);
        } elseif (is_a($source, "DocumentList")) {
            $this->formatCollection = new \FormatCollection();
            $this->formatCollection->useCollection($source);
        } elseif (is_a($source, "SearchDoc")) {
            $this->formatCollection = new \FormatCollection();
            /* @var \SearchDoc $source */
            $this->formatCollection->useCollection($source->getDocumentList());
        } else {
            /* the source is not a handled kind of source */
            throw new Exception("CRUD0500");
        }
        /* init the standard generator of url (redirect to the documents collection */
        $this->generateUrl = function ($document) {
            return URLUtils::generateURL("documents/{$document->initid}.json");
        };
    }

    /**
     * Add a callable function that generate the document uri propertie
     * The callable take a Doc $document and return the uri
     *
     * @param $callable
     */
    public function setGenerateURI($callable) {
        $this->generateUrl = $callable;
    }

    /**
     * add a set of attributes
     * Attributes that not part of the documents can be added
     *
     * @param array $attributes (array of attribute id)
     */
    public function setAttributes(Array $attributes)
    {
        foreach ($attributes as $currentAttribute) {
            $this->formatCollection->addAttribute($currentAttribute);
        }
    }

    /**
     * Add properties
     *
     * @param array $properties
     * @param bool $withDefault (add the standard default list)
     * @throws Exception
     */
    public function setProperties(Array $properties, $withDefault = false)
    {
        $this->properties = array();
        foreach ($properties as $currentProperty) {
            $this->addProperty($currentProperty);
        }
        if ($withDefault) {
            $this->properties = array_merge($this->properties, $this->defaultProperties);
        }
    }

    /**
     * Add a property
     *
     * @param $propertyId
     * @throws Exception
     */
    public function addProperty($propertyId)
    {
        $propertyKeys = \FormatCollection::getAvailableProperties();
        /* handle the non standard property all : all the usable properties */
        if ($propertyId === "all") {
            foreach ($propertyKeys as $propertyKey) {
                if (!in_array($propertyKey, static::$uselessProperties)) {
                    $this->properties[] = $propertyKey;
                }
            }
        } else {
            if (!in_array($propertyId, $propertyKeys) || in_array($propertyId, static::$uselessProperties)) {
                throw new Exception("CRUD0202", $propertyId);
            }
            $this->properties[] = $propertyId;
        }
    }

    /**
     * Add the default properties
     */
    public function useDefaultProperties()
    {
        $this->properties = $this->defaultProperties;
    }

    /**
     * Format the collection and return the array of result
     *
     * @return array
     * @throws \Dcp\Fmtc\Exception
     */
    public function format()
    {
        sort($this->properties);
        foreach ($this->properties as $currentProperty) {
            $this->formatCollection->addProperty($currentProperty);
        }
        /** Add the initid of the document (used to generate standard uri) **/
        $this->formatCollection->addProperty("initid");
        $this->formatCollection->setDecimalSeparator('.');
        $this->formatCollection->mimeTypeIconSize = 20;
        $this->formatCollection->useShowEmptyOption = false;
        $this->formatCollection->setPropDateStyle(\DateAttributeValue::isoWTStyle);
        /** Format uniformly the void multiple values */
        $this->formatCollection->setAttributeRenderHook(function ($info, $attribute)
        {
            if ($info === null) {
                /**
                 * @var \NormalAttribute $attribute
                 */
                if ($attribute->isMultiple()) {
                    $info = array();
                } else {
                    $info = new \StandardAttributeValue($attribute, null);
                }
            }
            return $info;
        });
        $generateUrl = $this->generateUrl;
        /** Add uri property and suppress state if no state **/
        $this->formatCollection->setDocumentRenderHook(function($values, \Doc $document) use ($generateUrl) {
            $values["uri"] = $generateUrl($document);
            if (isset($values["properties"]["state"]) && !$values["properties"]["state"]->reference) {
                unset($values["properties"]["state"]);
            }
            return $values;
        });
        
        return $this->formatCollection->render();
    }

    /**
     * Return the format collection
     *
     * @return \FormatCollection
     */
    public function getFormatCollection()
    {
        return $this->formatCollection;
    }
}
