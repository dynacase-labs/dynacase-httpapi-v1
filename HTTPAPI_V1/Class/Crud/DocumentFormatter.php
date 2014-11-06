<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
namespace Dcp\HttpApi\V1\Crud;


use Dcp\HttpApi\V1\DocManager\Exception;

class DocumentFormatter
{

    static protected $uselessProperties = array("lockdomainid", "domainid", "svalues", "ldapdn", "comment", "classname");
    /* @var \FormatCollection $formatCollection */
    protected $formatCollection;
    protected $defaultProperties = array("initid", "title", "revision", "state", "icon");
    protected $properties = array();

    public function __construct($source)
    {
        if (is_a($source, "Doc")) {
            $this->formatCollection = new \FormatCollection($source);
        } elseif (is_a($source, "DocumentList")) {
            $this->formatCollection = new \FormatCollection();
            $this->formatCollection->useCollection($source);
        } elseif (is_a($source, "SearchDoc")) {
            $this->formatCollection = new \FormatCollection();
            /* @var \SearchDoc $source */
            $this->formatCollection->useCollection($source->getDocumentList());
        } else {
            throw new Exception("CRUD0500");
        }
    }

    public function setAttributes(Array $attributes) {
        foreach($attributes as $currentAttribute) {
            $this->formatCollection->addAttribute($currentAttribute);
        }
    }

    public function setProperties(Array $properties)
    {
        $this->properties = array();
        foreach($properties as $currentProperty) {
            $this->addProperty($currentProperty);
        }
    }

    public function addProperty($propertyId) {
        if ($propertyId === "all") {
            foreach (array_keys(\Doc::$infofields) as $propertyKey) {
                if (!in_array($propertyKey, static::$uselessProperties)) {
                    $this->properties[] = $propertyKey;
                }
            }
        } else {
            if (!isset(\Doc::$infofields[$propertyId]) || in_array($propertyId, static::$uselessProperties)) {
                throw new Exception("CRUD0202", $propertyId);
            }
            $this->properties[] = $propertyId;
        }
    }

    public function useDefaultProperties()
    {
        $this->properties = $this->defaultProperties;
    }

    public function format()
    {
        sort($this->properties);
        foreach($this->properties as $currentProperty) {
            $this->formatCollection->addProperty($currentProperty);
        }
        $this->formatCollection->addProperty("initid");
        $this->formatCollection->setDecimalSeparator('.');
        $this->formatCollection->mimeTypeIconSize = 20;
        $this->formatCollection->useShowEmptyOption = false;
        return $this->formatCollection->render();
    }

} 