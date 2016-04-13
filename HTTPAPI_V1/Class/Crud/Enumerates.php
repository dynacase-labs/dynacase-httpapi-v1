<?php
/*
 * @author Anakeen
 * @package FDL
*/

namespace Dcp\HttpApi\V1\Crud;

use Dcp\HttpApi\V1\DocManager\DocManager as DocManager;

class Enumerates extends Crud
{
    
    const startsOperator = "startswith";
    const containsOperator = "contains";
    const orderByKeyword = "orderBy";
    const orderByKeyOption = "key";
    const orderByValueOption = "label";
    const orderByOrderOption = "none";
    /**
     * @var \DocFam
     */
    protected $family = null;
    protected $keywordFilter = '';
    protected $operatorFilter = self::containsOperator;
    protected $orderBy = self::orderByOrderOption;
    protected $enumid = null;
    //region CRUD part
    
    /**
     * Create new ressource
     * @throws Exception
     * @return mixed
     */
    public function create()
    {
        $exception = new Exception("CRUD0103", __METHOD__);
        $exception->setHttpStatus("501", "No yet implemented");
        throw $exception;
    }
    /**
     * Get ressource
     *
     * @param string $resourceId Resource identifier
     * @throws Exception
     * @return mixed
     */
    public function read($resourceId = "")
    {
        if ($resourceId === "") {
            $result = array(
                "uri" => $this->generateEnumUrl($this->family->name) ,
                "enumerates" => array() ,
            );
            $attributes = $this->family->getNormalAttributes();
            $enums = array_filter($attributes, function ($currentAttribute)
            {
                return $currentAttribute->type === "enum";
            });
            foreach ($enums as $currentEnum) {
                /* @var \NormalAttribute $currentEnum */
                $result["enumerates"][] = array(
                    "attributeId" => $currentEnum->id,
                    "label" => $currentEnum->getLabel() ,
                    "uri" => $this->generateEnumUrl($this->family->name, $currentEnum->id)
                );
            }
            return $result;
        }
        $attribute = $this->family->getAttribute($resourceId);
        if (!$attribute) {
            $exception = new Exception("CRUD0400", $resourceId, $this->family->name);
            $exception->setHttpStatus("404", "Attribute $resourceId not found");
            throw $exception;
        }
        if ($attribute->type !== "enum") {
            $exception = new Exception("CRUD0401", $resourceId, $attribute->type, $this->family->name);
            $exception->setHttpStatus("403", "Attribute $resourceId is not an enum");
            throw $exception;
        }
        /**
         * @var \NormalAttribute $attribute
         */
        $enums = $attribute->getEnumLabel();
        $info = array(
            "uri" => $this->generateEnumUrl($this->family->name, $resourceId) ,
            "label" => $attribute->getLabel()
        );
        
        $filterKeyword = $this->getFilterKeyword();
        $filterOperator = $this->getOperatorFilter();
        $pattern = '';
        if ($filterKeyword !== "") {
            switch ($filterOperator) {
                case self::containsOperator:
                    $pattern = sprintf("/%s/i", str_replace("/", "\\/", preg_quote($filterKeyword)));
                    break;

                case self::startsOperator:
                    $pattern = sprintf("/^%s/i", str_replace("/", "\\/", preg_quote($filterKeyword)));
                    break;
            }
        }
        $enumItems = array();
        foreach ($enums as $key => $label) {
            if ($key !== '' && $key !== ' ' && $key !== null) {
                if ($filterKeyword === "" || preg_match($pattern, $label)) {
                    $enumItems[] = array(
                        "key" => (string)$key,
                        "label" => $label
                    );
                }
            }
        }
        switch ($this->getOrderBy()) {
            case self::orderByKeyOption:
                usort($enumItems, function ($a, $b)
                {
                    if ($a['key'] == $b['key']) {
                        return 0;
                    }
                    return ($a['key'] < $b['key']) ? -1 : 1;
                });
                break;

            case self::orderByValueOption:
                $locale = \ApplicationParameterManager::getScopedParameterValue('CORE_LANG');
                $collator = new \Collator($locale);
                
                usort($enumItems, function ($a, $b) use ($collator)
                {
                    return $collator->compare($a['label'], $b['label']);
                });
                break;
        }
        $info["requestParameters"] = array(
            "operator" => $filterOperator,
            "keyword" => $filterKeyword,
            self::orderByKeyword => $this->getOrderBy()
        );
        $info["enumItems"] = $enumItems;
        
        return $info;
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
        $exception->setHttpStatus("501", "No yet implemented");
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
        $exception->setHttpStatus("501", "No yet implemented");
        throw $exception;
    }
    //endregion CRUD part
    
    /**
     * Analyze the parameters of the request
     *
     * @param array $parameters
     * @throws Exception
     */
    public function setContentParameters(array $parameters)
    {
        parent::setContentParameters($parameters);
        if (isset($this->contentParameters["keyword"])) {
            $this->setKeywordFilter($this->contentParameters["keyword"]);
        }
        if (isset($this->contentParameters["operator"])) {
            $this->setOperatorFilter($this->contentParameters["operator"]);
        }
        if (isset($this->contentParameters[self::orderByKeyword])) {
            $this->setOrderBy($this->contentParameters[self::orderByKeyword]);
        }
    }
    /**
     * Register the keyword
     *
     * @param $word
     */
    protected function setKeywordFilter($word)
    {
        if ($word === null) {
            $word = '';
        }
        $this->keywordFilter = $word;
    }
    /**
     * Return the operator filter
     *
     * @return string
     */
    public function getOperatorFilter()
    {
        return $this->operatorFilter;
    }
    /**
     * Set the operator filter
     *
     * @param string $operatorFilter
     * @throws Exception
     */
    public function setOperatorFilter($operatorFilter)
    {
        $availables = array(
            self::startsOperator,
            self::containsOperator
        );
        if (!in_array($operatorFilter, $availables)) {
            throw new Exception("CRUD0402", $operatorFilter, implode(", ", $availables));
        }
        $this->operatorFilter = $operatorFilter;
    }
    /**
     * Return the filter keyword
     *
     * @return string
     */
    protected function getFilterKeyword()
    {
        return $this->keywordFilter;
    }
    /**
     * Initialize the current family
     *
     * @param array $array
     * @throws Exception
     */
    public function setUrlParameters(Array $array)
    {
        parent::setUrlParameters($array);
        $familyId = isset($this->urlParameters["familyId"]) ? $this->urlParameters["familyId"] : false;
        $this->family = DocManager::getFamily($this->urlParameters["familyId"]);
        if (!$this->family) {
            $exception = new Exception("CRUD0200", $familyId);
            $exception->setHttpStatus("404", "Family not found");
            throw $exception;
        }
        $this->enumid = isset($this->urlParameters["identifier"]) ? $this->urlParameters["identifier"] : "";
    }
    
    protected function generateEnumUrl($famId, $enumId = "")
    {
        if ($enumId !== "") {
            $enumId.= ".json";
        }
        return $this->generateURL("families/$famId/enumerates/$enumId");
    }
    /**
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }
    /**
     * @param string $orderBy
     *
     * @throws Exception
     */
    protected function setOrderBy($orderBy)
    {
        $availables = array(
            self::orderByOrderOption,
            self::orderByKeyOption,
            self::orderByValueOption
        );
        if (!in_array($orderBy, $availables)) {
            throw new Exception("CRUD0403", $orderBy, implode(", ", $availables));
        }
        $this->orderBy = $orderBy;
    }
}
